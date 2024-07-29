/* eslint-disable react/no-unescaped-entities   */
/* eslint-disable react-hooks/exhaustive-deps */
import { Section, Table, Button, ButtonColor } from '@refactco/ui-kit';
const { useState, useEffect } = wp.element;
import Spinner from '../../components/spinner';
import { scheduleImportHelper } from './scheduleImportHelper';
import Modal from '../../components/modal';
import { styled } from 'styled-components';

const ScheduledImport = () => {
	const [ state, setState ] = useState( {
		loadingScheduledImports: false,
		scheduledImports: [],
		rowData: [],
		selectedRowInfo: null,
		showMoreInfoModal: false,
		showDeleteScheduleModal: false,
		loadingDeleteAction: false,
	} );
	const {
		loadingScheduledImports,
		rowData,
		selectedRowInfo,
		showMoreInfoModal,
		showDeleteScheduleModal,
	} = state;

	const helper = scheduleImportHelper( state, setState );
	const {
		getScheduledImports,
		moreInfoModalCloseHandler,
		showMoreInfoModalHandler,
		getRecurrenceText,
		showDeleteScheduleModalHandler,
		deleteScheduleModalCloseHandler,
		loadingDeleteAction,
		deleteScheduleHandler,
	} = helper;

	const actions = [
		{
			color: ButtonColor.BLUE,
			text: 'More Info',
			onClick: ( index ) => {
				showMoreInfoModalHandler( index );
			},
		},
		{
			color: ButtonColor.RED,
			text: 'Delete',
			onClick: ( index ) => {
				showDeleteScheduleModalHandler( index );
			},
		},
	];

	useEffect( () => {
		( async () => {
			await getScheduledImports();
		} )();
	}, [] );

	const headers = [ 'Schedule Id', 'Recurrency ', 'Publication Id' ];

	return (
		<Section>
			{ loadingScheduledImports ? (
				<Spinner />
			) : (
				<Table
					headers={ headers }
					actions={ actions }
					dataRows={ rowData }
					noDraggable
				/>
			) }
			<Modal
				show={ showMoreInfoModal }
				modalClosed={ moreInfoModalCloseHandler }
			>
				{ selectedRowInfo && (
					<>
						<h3>Schedule Information</h3>
						<p>
							<b>Schedule Id:</b> { selectedRowInfo.id } <br />
						</p>
						<p>
							<b>Publication Id:</b>{ ' ' }
							{
								selectedRowInfo.params[ 0 ].credentials
									.publication_id
							}{ ' ' }
							<br />
						</p>
						<p>
							<b>Content Type:</b>{ ' ' }
							{ selectedRowInfo.params[ 0 ].audience } <br />
						</p>
						<p>
							Campaign tags are imported as{ ' ' }
							<b>
								{
									selectedRowInfo.params[ 0 ]
										.import_cm_tags_as
								}
							</b>{ ' ' }
							, with the import option set to{ ' ' }
							<b>{ selectedRowInfo.params[ 0 ].import_option }</b>
							.<br />
						</p>
						<p>
							The post type specified is{ ' ' }
							<b>{ selectedRowInfo.params[ 0 ].post_type }</b>.{ ' ' }
							<br />
						</p>
						<p>
							This schedule recurs{ ' ' }
							<b>
								{ getRecurrenceText(
									selectedRowInfo.params[ 0 ]
										.schedule_settings
								) }
							</b>
							.<br />{ ' ' }
						</p>
						<p>
							The taxonomy used is{ ' ' }
							<b>{ selectedRowInfo.params[ 0 ].taxonomy }</b>.
							<br />
						</p>
						<p>
							Campaign status on Beehiiv is mapped to the post
							status on WordPress as follows:{ ' ' }
							{ selectedRowInfo.params[ 0 ].post_status
								.confirmed && (
								<>
									<b>'Confirmed'</b> corresponds to{ ' ' }
									<b>
										{
											selectedRowInfo.params[ 0 ]
												.post_status.confirmed
										}
									</b>
								</>
							) }
							{ selectedRowInfo.params[ 0 ].post_status.draft && (
								<>
									{ selectedRowInfo.params[ 0 ].post_status
										.confirmed
										? ','
										: '' }
									<b>'Draft'</b> corresponds to{ ' ' }
									<b>
										{
											selectedRowInfo.params[ 0 ]
												.post_status.draft
										}
									</b>
								</>
							) }
							{ selectedRowInfo.params[ 0 ].post_status
								.archived && (
								<>
									{ selectedRowInfo.params[ 0 ].post_status
										.draft ||
									selectedRowInfo.params[ 0 ].post_status
										.confirmed
										? ', and '
										: '' }
									<b>'Archived'</b> corresponds to{ ' ' }
									<b>
										{
											selectedRowInfo.params[ 0 ]
												.post_status.archived
										}
									</b>
								</>
							) }
							.
						</p>
					</>
				) }
			</Modal>
			<Modal
				show={ showDeleteScheduleModal }
				modalClosed={ deleteScheduleModalCloseHandler }
			>
				{ selectedRowInfo && (
					<>
						<h3>
							Are you sure you want to delete schedule with id{ ' ' }
							{ selectedRowInfo.id }
						</h3>
						<ButtonContainer>
							<Button
								color={ ButtonColor.RED }
								onClick={ deleteScheduleHandler }
								disabled={ loadingDeleteAction }
							>
								Delete Schedule
							</Button>
							<Button
								onClick={ deleteScheduleModalCloseHandler }
								disabled={ loadingDeleteAction }
							>
								Cancel
							</Button>
						</ButtonContainer>
					</>
				) }
			</Modal>
		</Section>
	);
};

const ButtonContainer = styled.div`
	display: flex;
	justify-content: flex-start;
	margin-top: 20px;
	gap: 1rem;
`;

export default ScheduledImport;
