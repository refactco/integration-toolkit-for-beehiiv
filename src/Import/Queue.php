<?php

namespace Re_Beehiiv\Import;

class Queue {
    // Constants
    const TIMESTAMP_2_MIN = 2 * MINUTE_IN_SECONDS;
    const TIMESTAMP_30_MIN = 1800;
    const TIMESTAMP_1_HOUR = 3600;
    const TIMESTAMP_2_HOUR = 7200;
    const TIMESTAMP_1_DAY  = 86400;
    const TIMESTAMP_7_DAY  = 604800;
    const MAX_RETRY_COUNT  = 3;

    private $action = 'bulk_import';
    private $timestamp = MINUTE_IN_SECONDS;

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function addToQueue($request)
    {
        if (as_has_scheduled_action($this->action, $request, $request['group']) === false) {
            as_schedule_single_action(time() + $this->timestamp, $this->action, $request, $request['group']);
        }
    }


    public function queueCallback($group_name, $args)
    {
        $requestKey = $this->getRequestKey($args['id']);
        $retryCount = get_transient($requestKey);
        if ($retryCount === false || $retryCount < self::MAX_RETRY_COUNT) {
            $res = (new Create_Post($args))->Process();
            if ($res["success"] === false) {
                $retryCount = $retryCount === false ? 1 : $retryCount + 1;
                set_transient($requestKey, $retryCount, self::TIMESTAMP_2_MIN);
            } else {
                delete_transient($requestKey);
            }
        }
    }

    public function queueHandler()
    {
        add_action($this->action, [$this, 'queueCallback'], 10, 2);
    }

    public function getRequestKey($request)
    {
        return 're_beehiiv_' . md5(json_encode($request));
    }


    /**
     * Get all scheduled actions for a given group and status
     * 
     * @param string $group
     * @param string $status
     * @return array
     */
    public function get_manual_actions($group = '', $status = '') {

        $args = [
            'hook' => $this->action,
            'group' => $group ? $group : '',
            'per_page' => -1,
        ];

        if ($status) {
            $args['status'] = $status;
        }
    
        $actions = as_get_scheduled_actions($args);

        return $actions;
    }
}