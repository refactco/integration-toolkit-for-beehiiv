/* eslint-disable react/no-unescaped-entities   */
/* eslint-disable camelcase   */
/* eslint-disable array-callback-return   */
const apiFetch = wp.apiFetch;
import { toast } from 'react-toastify';
import { logger } from '../../common/common-function';

export function scheduleImportHelper( state, setState ) {
	function getRecurrenceText( schedule_settings ) {
		// Helper function to convert 24-hour time to 12-hour time with AM/PM
		function formatTime( time ) {
			const [ hour, minute ] = time.split( ':' ).map( Number );
			const period = hour >= 12 ? 'PM' : 'AM';
			const formattedHour = hour % 12 || 12; // Convert '0' to '12' for 12-hour clock
			const formattedMinute = minute.toString().padStart( 2, '0' );
			return `${ formattedHour }:${ formattedMinute } ${ period }`;
		}

		if ( schedule_settings.enabled !== 'on' ) {
			return 'Schedule is disabled';
		}

		let text = 'Every';

		switch ( schedule_settings.frequency ) {
			case 'weekly':
				text += ` ${
					schedule_settings.specific_day.charAt( 0 ).toUpperCase() +
					schedule_settings.specific_day.slice( 1 )
				}`;
				if ( schedule_settings.time ) {
					text += ` at ${ formatTime( schedule_settings.time ) }`;
				}
				break;
			case 'daily':
				if ( schedule_settings.time ) {
					text += ` day at ${ formatTime( schedule_settings.time ) }`;
				}
				break;
			case 'hourly':
				text += ` ${ schedule_settings.specific_hour } hour`;
				break;
			default:
				return 'Invalid frequency';
		}

		return text;
	}
	async function getScheduledImports() {
		try {
			setState( {
				...state,
				loadingScheduledImports: true,
			} );
			const response = await apiFetch( {
				path: 'itfb/v1/get-scheduled-imports',
			} );
			const rowData = [];
			response.map( ( item ) => {
				const recurrency = getRecurrenceText(
					item.params[ 0 ].schedule_settings
				);
				rowData.push( [
					item.id,
					recurrency,
					item.params[ 0 ].credentials.publication_id,
				] );
			} );
			setState( {
				...state,
				scheduledImports: response,
				rowData,
				loadingScheduledImports: false,
			} );
		} catch ( error ) {
			logger( error );
			toast.error( error.message );
		}
	}

	function moreInfoModalCloseHandler() {
		setState( {
			...state,
			showMoreInfoModal: false,
			selectedRowInfo: null,
		} );
	}

	function deleteScheduleModalCloseHandler() {
		setState( {
			...state,
			showDeleteScheduleModal: false,
			selectedRowInfo: null,
		} );
	}

	function showMoreInfoModalHandler( index ) {
		const row = state.scheduledImports[ index ];
		setState( {
			...state,
			selectedRowInfo: row,
			showMoreInfoModal: true,
		} );
	}

	function showDeleteScheduleModalHandler( index ) {
		const row = state.scheduledImports[ index ];
		setState( {
			...state,
			selectedRowInfo: row,
			showDeleteScheduleModal: true,
		} );
	}

	async function deleteScheduleHandler() {
		const { selectedRowInfo, scheduledImports, rowData } = state;
		if ( selectedRowInfo ) {
			try {
				setState( ( prevState ) => ( {
					...prevState,
					loadingDeleteAction: true,
				} ) );
				const response = await apiFetch( {
					path: `itfb/v1/delete-scheduled-import/?id=${ selectedRowInfo.id }`,
					method: 'DELETE',
				} );

				if ( response && response.id ) {
					const newScheduledImports = scheduledImports.filter(
						( item ) => item.id !== selectedRowInfo.id
					);
					const newRowData = rowData.filter(
						( item ) => item[ 0 ] !== selectedRowInfo.id
					);
					logger( newScheduledImports, newRowData );
					setState( ( prevState ) => ( {
						...prevState,
						scheduledImports: newScheduledImports,
						rowData: newRowData,
					} ) );
					toast.success( response.message );
				} else {
					toast.error( response.message );
				}
			} catch ( error ) {
				logger( error );
				toast.error( error.message );
			} finally {
				setState( ( prevState ) => ( {
					...prevState,
					loadingDeleteAction: false,
					selectedRowInfo: null,
					showDeleteScheduleModal: false,
				} ) );
			}
		}
	}

	return {
		getScheduledImports,
		moreInfoModalCloseHandler,
		showMoreInfoModalHandler,
		getRecurrenceText,
		deleteScheduleHandler,
		showDeleteScheduleModalHandler,
		deleteScheduleModalCloseHandler,
	};
}
