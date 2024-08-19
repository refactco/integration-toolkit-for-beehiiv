/* eslint-disable camelcase */
/* eslint-disable no-nested-ternary */
/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable no-unused-vars */
const { useState, useEffect } = wp.element;
import {
	Button,
	Input,
	InputType,
	Accordion,
	Checkbox,
	Select,
	Toggle,
	Tooltip,
	TooltipMode,
	TooltipPlace,
} from '@refactco/ui-kit';
import { manaulImportHelper } from './manaulImportHelper';
import { styled } from 'styled-components';
import Backdrop from '../../components/backdrop';
import Spinner from '../../components/spinner';
import ImportPercentage from '../../components/import-percentage';
import { logger } from '../../common/common-function';
import useLocalStorage from '../../hooks/useLocalStorage';

const ManualImport = () => {
	const [ state, setState ] = useState( {
		apiKey: '',
		clientId: '',
		publishedCampaigns: true,
		publishedWrodpressPostStatus: 'publish',
		draftededCampaigns: true,
		draftedWrodpressPostStatus: 'draft',
		archivedCampaigns: true,
		archivedWrodpressPostStatus: 'future',
		postStatuses: [],
		postTypes: [],
		selectedPostType: '',
		taxonomies: [],
		selectedTaxonomy: '',
		terms: [],
		selectedTerm: '',
		authors: [],
		selectedAuthor: '',
		importCampaignTagAs: 'post_tag',
		importOption: 'new',
		enableSchedule: false,
		frequency: 'hourly',
		runHour: 1,
		runDay: 'monday',
		runTime: '00:00',
		serverTime: '',
		errors: {},
		disableInput: false,
		isFormDisabled: true,
		intervalId: [],
		startImporting: false,
		progressValue: 0,
		loading: true,
		jobAction: 'pause',
		loadingJobAction: false,
		scheduleId: null,
		contentType: 'free',
	} );

	const {
		apiKey,
		publicationId,
		publishedCampaigns,
		publishedWrodpressPostStatus,
		draftededCampaigns,
		draftedWrodpressPostStatus,
		archivedCampaigns,
		archivedWrodpressPostStatus,
		postStatuses,
		postTypes,
		selectedPostType,
		taxonomies,
		selectedTaxonomy,
		terms,
		selectedTerm,
		authors,
		selectedAuthor,
		importCampaignTagAs,
		importOption,
		enableSchedule,
		frequency,
		runHour,
		runDay,
		runTime,
		serverTime,
		errors,
		disableInput,
		isFormDisabled,
		intervalId,
		startImporting,
		progressValue,
		loading,
		jobAction,
		loadingJobAction,
		scheduleId,
		contentType,
	} = state;

	const [ 
		groupName, 
		setGroupName, 
		removeGroupName 
	] = useLocalStorage(
		'groupName',
		null
	);
	const [
		totalQueuedCampaigns,
		setTotalQueuedCampaigns,
		removeTotalQueuedCampaigns,
	] = useLocalStorage( 'totalQueuedCampaigns', null );

	const [
		scheduleID,
		setScheduleID,
		removeScheduleID,
	] = useLocalStorage( 'scheduleID', null );

	const helper = manaulImportHelper(
		state,
		setState,
		groupName,
		setGroupName,
		removeGroupName,
		setTotalQueuedCampaigns,
		removeTotalQueuedCampaigns,
		scheduleID,
		setScheduleID,
		removeScheduleID,
	);

	const {
		getDefaultOptions,
		handleInputChange,
		startImportHandler,
		handleBlur,
		checkCurrentImportStatus,
		jobActionHandler,
	} = helper;

	useEffect( () => {
		( async () => {
			await getDefaultOptions();
			await checkCurrentImportStatus();
		} )();
	}, [] );

	useEffect( () => {
		return () => {
			if ( intervalId ) {
				clearInterval( state.intervalId );
			}
		};
	}, [ intervalId ] );

	return (
		<>
			{ disableInput && (
				<Backdrop
					show
					modalClosed={ () => logger( 'Fetching Campaigns ...' ) }
				>
					<Spinner label="Fetching campaigns from Beehiiv. Please wait..." />
				</Backdrop>
			) }
			{ startImporting ? (
				<ImportPercentage
					progressValue={ progressValue }
					jobAction={ jobAction }
					jobActionHandler={ jobActionHandler }
					loadingJobAction={ loadingJobAction }
					scheduleId={ scheduleId }
				/>
			) : loading ? (
				<Spinner />
			) : (
				<>
					<h1>Import Campaign Data</h1>
					<form>
						<Accordion
							noDraggable
							transitionTimeout={ 300 }
							items={ [
								{
									header: 'Step 1: Choose Data from Beehiiv',
									content: (
										<>
											<Container>
												<InputContainer>
													<div>
														<label
															className="label"
															htmlFor="apiKey"
														>
															API Key
															<Tooltip
																id="apiKey"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="The API Key is a unique identifier that allows you to access your Beehiiv account data. You can find your API Key by logging into your Beehiiv account and go to Settings > Integrations > which will open up the API tab"
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Input
															type={
																InputType.TEXT
															}
															value={ apiKey }
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'apiKey'
																)
															}
															onBlur={ () =>
																handleBlur(
																	'apiKey',
																	apiKey
																)
															}
															required
															hasError={
																errors.apiKey
															}
															disabled={
																disableInput
															}
														/>
													</div>
													<div>
														<label
															className="label"
															htmlFor="publicationId"
														>
															Publication ID
															<Tooltip
																id="publicationId"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="The Publication ID is a unique identifier that allows you to access your Beehiiv account data. You can find your Publication ID by logging into your Beehiiv account and go to Settings > Integrations > which will open up the API tab"
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Input
															type={
																InputType.TEXT
															}
															value={
																publicationId
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'publicationId'
																)
															}
															onBlur={ () =>
																handleBlur(
																	'publicationId',
																	publicationId
																)
															}
															required
															hasError={
																errors.publicationId
															}
															disabled={
																disableInput
															}
														/>
													</div>
												</InputContainer>
												{ errors.apiKey && (
													<ErrorContainer>
														{ errors.apiKey }
													</ErrorContainer>
												) }
												{ errors.publicationId && (
													<ErrorContainer>
														{ errors.publicationId }
													</ErrorContainer>
												) }
												<Divider />
											</Container>
											<Container>
												<InputContainer>
													<div>
														<label
															className="label"
															htmlFor="contentType"
														>
															Content Type
															<Tooltip
																id="contentType"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="Choose the content subscription level you'd like to import. 'Free' pertains to content available without subscription fees, while 'Premium' is exclusive paid content"
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Select
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'contentType'
																)
															}
															options={ [
																{
																	label: 'Free',
																	value: 'free',
																},
																{
																	label: 'Premium',
																	value: 'premium',
																},
																{
																	label: 'All',
																	value: 'all',
																}
															] }
															value={
																contentType
															}
															required
															disabled={
																disableInput
															}
														/>
													</div>
													<div></div>
													<Divider />
												</InputContainer>
											</Container>

											<h3>
												Select type of campaigns that
												you want migrated from Beehiiv
												<Tooltip
													id="campaignType"
													mode={ TooltipMode.DARK }
													place={ TooltipPlace.TOP }
													content="Select the visibility status of the posts within. 'Published' posts are live on, 'Archived' posts are stored but not visible to the audience, and 'Draft' posts are unpublished content."
												>
													<div className="question-icon">
														?
													</div>
												</Tooltip>
											</h3>
											<Container>
												<InputContainer>
													<b>
														Campaign status on
														Beehiiv
													</b>
													<b>
														Post Status On Wordpress
													</b>
												</InputContainer>
												<InputContainer>
													<Checkbox
														label="Published Campaigns"
														checked={
															publishedCampaigns
														}
														onChange={ ( value ) =>
															handleInputChange(
																value,
																'publishedCampaigns'
															)
														}
														disabled={
															disableInput
														}
													/>
													{ publishedCampaigns && (
														<Select
															value={
																publishedWrodpressPostStatus
															}
															options={
																postStatuses
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'publishedWrodpressPostStatus'
																)
															}
															disabled={
																disableInput
															}
														/>
													) }
												</InputContainer>
												<InputContainer>
													<Checkbox
														label="Draft Campaigns"
														checked={
															draftededCampaigns
														}
														onChange={ ( value ) =>
															handleInputChange(
																value,
																'draftededCampaigns'
															)
														}
														disabled={
															disableInput
														}
													/>
													{ draftededCampaigns && (
														<Select
															value={
																draftedWrodpressPostStatus
															}
															options={
																postStatuses
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'draftedWrodpressPostStatus'
																)
															}
															disabled={
																disableInput
															}
														/>
													) }
												</InputContainer>
												<InputContainer>
													<Checkbox
														label="Archived Campaigns"
														checked={
															archivedCampaigns
														}
														onChange={ ( value ) =>
															handleInputChange(
																value,
																'archivedCampaigns'
															)
														}
														disabled={
															disableInput
														}
													/>
													{ archivedCampaigns && (
														<Select
															value={
																archivedWrodpressPostStatus
															}
															options={
																postStatuses
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'archivedWrodpressPostStatus'
																)
															}
															disabled={
																disableInput
															}
														/>
													) }
												</InputContainer>
												{ errors.campaignStatus && (
													<ErrorContainer>
														{
															errors.campaignStatus
														}
													</ErrorContainer>
												) }
											</Container>
										</>
									),
								},
								{
									header: 'Step 2: Import Data to WordPress',
									content: (
										<>
											<Container>
												<InputContainer>
													<div>
														<label
															className="label"
															htmlFor="postType"
														>
															Select Post Type
															<Tooltip
																id="postType"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="Define how you'd like the imported content to be categorized within your WordPress site. 'Post Type' determines the format of your content, such as a blog post, page, or custom post type."
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Select
															options={ postTypes.map(
																( {
																	post_type,
																} ) => ( {
																	label:
																		post_type
																			.charAt(
																				0
																			)
																			.toUpperCase() +
																		post_type.slice(
																			1
																		), // Capitalize the first letter
																	value: post_type,
																} )
															) }
															value={
																selectedPostType
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'selectedPostType'
																)
															}
															required
															disabled={
																disableInput
															}
														/>
													</div>

													{ taxonomies &&
														taxonomies.length !==
															0 && (
															<div>
																<label
																	className="label"
																	htmlFor="taxonomy"
																>
																	Select
																	Taxonomy
																	<Tooltip
																		id="taxonomy"
																		mode={
																			TooltipMode.DARK
																		}
																		place={
																			TooltipPlace.TOP
																		}
																		content="'Taxonomy' allows you to classify your content into categories and tags for easy searching and organization."
																	>
																		<div className="question-icon">
																			?
																		</div>
																	</Tooltip>
																</label>
																<Select
																	options={ taxonomies.map(
																		( {
																			taxonomy_name,
																			taxonomy_slug,
																		} ) => ( {
																			label:
																				taxonomy_name ??
																				'',
																			value:
																				taxonomy_slug ??
																				'',
																		} )
																	) }
																	value={
																		selectedTaxonomy
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'selectedTaxonomy'
																		)
																	}
																	disabled={
																		disableInput
																	}
																/>
															</div>
														) }
													{ terms &&
														terms.length !== 0 &&
														taxonomies &&
														taxonomies.length !==
															0 && (
															<div>
																<label
																	className="label"
																	htmlFor="term"
																>
																	Select Term
																	<Tooltip
																		id="term"
																		mode={
																			TooltipMode.DARK
																		}
																		place={
																			TooltipPlace.TOP
																		}
																		content="'Term' refers to the specific category or tag that you'd like to assign to your imported content."
																	>
																		<div className="question-icon">
																			?
																		</div>
																	</Tooltip>
																</label>
																<Select
																	options={ terms.map(
																		( {
																			term_id,
																			term_name,
																		} ) => ( {
																			label:
																				term_name ??
																				'',
																			value:
																				term_id ??
																				'',
																		} )
																	) }
																	value={
																		selectedTerm
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'selectedTerm'
																		)
																	}
																	disabled={
																		disableInput
																	}
																/>
															</div>
														) }
												</InputContainer>
												<Divider />
												<InputContainer>
													{ authors &&
														authors.length !==
															0 && (
															<div>
																<label
																	className="label"
																	htmlFor="author"
																>
																	Select
																	Author
																	<Tooltip
																		id="author"
																		mode={
																			TooltipMode.DARK
																		}
																		place={
																			TooltipPlace.TOP
																		}
																		content="Choose a WordPress user to be designated as the author of the imported content. This user will be credited for the posts and will have edit rights over them."
																	>
																		<div className="question-icon">
																			?
																		</div>
																	</Tooltip>
																</label>
																<Select
																	options={ authors.map(
																		( {
																			display_name,
																			id,
																		} ) => ( {
																			label:
																				display_name ??
																				'',
																			value:
																				id ??
																				'',
																		} )
																	) }
																	value={
																		selectedAuthor
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'selectedAuthor'
																		)
																	}
																	required
																	disabled={
																		disableInput
																	}
																/>
															</div>
														) }
													<div>
														<label
															className="label"
															htmlFor="importCampaignTagsAs"
														>
															Import campaign tags
															as
															<Tooltip
																id="importCampaignTagsAs"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="Tags help organize and categorize your content. This setting allows you to pull tags associated with your content and assign them to specific taxonomies and terms within WordPress."
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Select
															options={ [
																{
																	label: 'Post Tag',
																	value: 'post_tag',
																},
																{
																	label: 'Category',
																	value: 'category',
																},
															] }
															value={
																importCampaignTagAs
															}
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'importCampaignTagAs'
																)
															}
															required
															disabled={
																disableInput
															}
														/>
													</div>
													<div>
														<label
															className="label"
															htmlFor="importOption"
														>
															Import Option
															<Tooltip
																id="importOption"
																mode={
																	TooltipMode.DARK
																}
																place={
																	TooltipPlace.TOP
																}
																content="Select how you'd like to handle the incoming content. 'Import new items' will only add new content, 'Update existing items' will overwrite existing content with updates from, and 'Do both' will import new items while updating any matching existing content."
															>
																<div className="question-icon">
																	?
																</div>
															</Tooltip>
														</label>
														<Select
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'importOption'
																)
															}
															options={ [
																{
																	label: 'New',
																	value: 'new',
																},
																{
																	label: 'Update',
																	value: 'update',
																},
																{
																	label: 'Both',
																	value: 'both',
																},
															] }
															value={
																importOption
															}
															required
															disabled={
																disableInput
															}
														/>
													</div>
												</InputContainer>
											</Container>
											<Divider />
											<Toggle
												label="Schedule"
												onChange={ ( value ) =>
													handleInputChange(
														value,
														'enableSchedule'
													)
												}
												checked={ enableSchedule }
												disabled={ disableInput }
											/>
											<Tooltip
												id="schedule"
												mode={ TooltipMode.DARK }
												place={ TooltipPlace.TOP }
												content="Schedule the automatic importing process by specifying how often the system should check for new content."
											>
												<div className="question-icon">
													?
												</div>
											</Tooltip>
											<Container>
												<InputContainer>
													{ enableSchedule && (
														<Select
															label="Frequency"
															options={ [
																{
																	label: 'Hourly',
																	value: 'hourly',
																},
																{
																	label: 'Daily',
																	value: 'daily',
																},
																{
																	label: 'Weekly',
																	value: 'weekly',
																},
															] }
															value={ frequency }
															onChange={ (
																value
															) =>
																handleInputChange(
																	value,
																	'frequency'
																)
															}
															disabled={
																disableInput
															}
														/>
													) }
													{ enableSchedule &&
														frequency ===
															'hourly' && (
															<>
																<Input
																	type={
																		InputType.NUMBER
																	}
																	label="Defines the hour"
																	help="Enter the desired time intervals in hours and set the frequency of auto imports from your to your WordPress site."
																	placeholder="1-24"
																	min="1"
																	max="24"
																	value={
																		runHour
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'runHour'
																		)
																	}
																	onBlur={ () =>
																		handleBlur(
																			'runHour',
																			runHour
																		)
																	}
																	hasError={
																		errors.runHour
																	}
																	disabled={
																		disableInput
																	}
																/>
															</>
														) }
													{ enableSchedule &&
														frequency ===
															'daily' && (
															<>
																<Input
																	type={
																		InputType.TEXT
																	}
																	label="Specifies the time"
																	help={ `Important: The time refers to your server current time, which is not necessarily in your personal timezone. Current Server Time :  ${ serverTime })` }
																	placeholder='Use "HH:mm" (e.g., "02:00")'
																	value={
																		runTime
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'runTime'
																		)
																	}
																	onBlur={ () =>
																		handleBlur(
																			'runTime',
																			runTime
																		)
																	}
																	hasError={
																		errors.runTime
																	}
																	disabled={
																		disableInput
																	}
																/>
															</>
														) }
													{ enableSchedule &&
														frequency ===
															'weekly' && (
															<>
																<Select
																	label="Specifies the days"
																	options={ [
																		{
																			label: 'Monday',
																			value: 'monday',
																		},
																		{
																			label: 'Tuesday',
																			value: 'tuesday',
																		},
																		{
																			label: 'Wednesday',
																			value: 'wednesday',
																		},
																		{
																			label: 'Thursday',
																			value: 'thursday',
																		},
																		{
																			label: 'Friday',
																			value: 'friday',
																		},
																		{
																			label: 'Saturday ',
																			value: 'saturday ',
																		},
																		{
																			label: 'Sunday ',
																			value: 'sunday ',
																		},
																	] }
																	value={
																		runDay
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'runDay'
																		)
																	}
																	disabled={
																		disableInput
																	}
																/>
																<Input
																	type={
																		InputType.TEXT
																	}
																	label="Specifies the time"
																	help={ `Important: The time refers to your server current time, which is not necessarily in your personal timezone. Current Server Time :  ${ serverTime }` }
																	placeholder='Use "HH:mm" (e.g., "02:00")'
																	value={
																		runTime
																	}
																	onChange={ (
																		value
																	) =>
																		handleInputChange(
																			value,
																			'runTime'
																		)
																	}
																	onBlur={ () =>
																		handleBlur(
																			'runTime',
																			runTime
																		)
																	}
																	hasError={
																		errors.runTime
																	}
																	disabled={
																		disableInput
																	}
																/>
															</>
														) }
												</InputContainer>
												{ errors.runHour &&
													enableSchedule &&
													frequency === 'hourly' && (
														<ErrorContainer>
															{ errors.runHour }
														</ErrorContainer>
													) }
												{ errors.runTime &&
													enableSchedule &&
													frequency !== 'hourly' && (
														<ErrorContainer>
															{ errors.runTime }
														</ErrorContainer>
													) }
											</Container>
										</>
									),
								},
							] }
						/>
						<div
							style={ {
								display: 'flex',
								gap: '4px',
								marginTop: '20px',
							} }
						>
							<Button
								type="submit"
								onClick={ startImportHandler }
								disabled={ isFormDisabled }
							>
								Start Import
							</Button>
						</div>
					</form>
				</>
			) }
		</>
	);
};

const Container = styled.div`
	display: flex;
	flex-direction: column;
	height: 100%;
`;

export const InputContainer = styled.div`
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	padding-bottom: ${ ( { paddingBottom } ) => paddingBottom || '1rem' };
	gap: ${ ( { gap } ) => gap || '1rem' };
	> * {
		flex: 1;
	}
	@media ( max-width: 782px ) {
		flex-direction: column;
	}
`;

const Divider = styled.div`
	width: 100%;
	height: 1px;
	margin-bottom: 1rem;
	background: #d7dbdb;
`;

const ErrorContainer = styled.div`
	color: red;
`;

export default ManualImport;
