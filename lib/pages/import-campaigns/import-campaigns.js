/* eslint-disable react-hooks/exhaustive-deps */
const { useState, useEffect } = wp.element;
import {
	Route,
	Routes,
	Navigate,
	useNavigate,
	useLocation,
} from 'react-router-dom';
import styled from 'styled-components';
import ManualImport from '../../container/manaulImport/manualImport';
import ScheduledImport from '../../container/scheduleImport/scheduleImport';
import { importCampaignsHelper, tabs } from './import-campaigns-helper';

const ImportCampaigns = () => {
	const [ state, setState ] = useState( {
		activeIndex: 0,
	} );

	const navigate = useNavigate();
	const location = useLocation();
	const helper = importCampaignsHelper( state, setState, navigate );
	const { handleTabClick } = helper;
	const { activeIndex } = state;

	useEffect( () => {
		if ( location.pathname.includes( 'scheduled' ) ) {
			setState( { ...state, activeIndex: 1 } );
		} else if ( location.pathname.includes( 'manual' ) ) {
			setState( { ...state, activeIndex: 0 } );
		}
	}, [ location.pathname ] );

	return (
		<>
			<TabPanelContainer>
				{ tabs.map( ( tab, index ) => (
					<TabItem
						key={ tab.name }
						isActive={ index === activeIndex }
						onClick={ () => handleTabClick( index ) }
					>
						{ tab.title }
					</TabItem>
				) ) }
			</TabPanelContainer>
			<TabContent>
				<Routes>
					<Route index element={ <ManualImport /> } />
					<Route path="manual" element={ <ManualImport /> } />
					<Route path="scheduled" element={ <ScheduledImport /> } />
				</Routes>
			</TabContent>
		</>
	);
};

// Styled component for the tab panel container
export const TabPanelContainer = styled.div`
	background-color: white; // Set background color to white
	display: flex; // Use flexbox to align items
	align-items: center; // Ensure items are centered vertically
	box-shadow: 0px 2px 4px 0px rgba( 0, 0, 0, 0.08 );
	padding: 20px 0px 0px 20px;
	box-sizing: border-box;
`;

export const TabItem = styled.div`
	padding: 10px 20px;
	margin: 0 15px; // Consistent spacing between tabs
	cursor: pointer;
	color: ${ ( props ) =>
		props.isActive
			? '#2e9e62'
			: '#003233' }; // Green for active tab, grey for inactive
	background-color: transparent; // No background color
	font-size: 13px; // Larger font size for better readability
	font-weight: ${ ( props ) =>
		props.isActive
			? 'bold'
			: 'normal' }; // Bold for active, normal for inactive
	border-bottom: ${ ( props ) =>
		props.isActive
			? '3px solid #2e9e62'
			: '3px solid transparent' }; // Bold green bottom border for active
	transition: all 0.3s; // Smooth transition for color and border changes

	&:hover {
		color: #2e9e62; // Green color on hover
		border-bottom: 3px solid #2e9e62; // Maintain bold green bottom border on hover
	}
`;

// Styled component for the content area under tabs
export const TabContent = styled.div`
	box-shadow: 0px 2px 4px 0px rgba( 0, 0, 0, 0.08 );
	padding: 25px 45px;
	box-sizing: border-box;
	background-color: white; // Match the body's background color
	// Removing border for a seamless integration with the rest of the page
`;

export default ImportCampaigns;
