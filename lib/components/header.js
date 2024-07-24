/* eslint-disable react-hooks/exhaustive-deps */
const { useState, useEffect } = wp.element;
import { Header as RefactHeader } from '@refactco/ui-kit';
import { useNavigate, useLocation } from 'react-router-dom';

const Header = () => {
	const navigate = useNavigate();
	const location = useLocation();

	const [ activeItemIndex, setActiveItemIndex ] = useState( 0 );
	const items = [
		{
			item: 'import-campaigns',
			title: 'Import Campaigns',
			subHeaderItems: [
				{
					name: 'import_campaigns_title',
					title: 'Import your campaigns from Campaign Beehive',
				},
			],
			onClick: () => {
				navigate( '/import-campaigns' );
			},
		},
		{
			item: 'about',
			title: 'About',
			onClick: () => {
				navigate( '/about' );
			},
		},
	];

	const handleSelectItem = ( index ) => {
		setActiveItemIndex( index );
	};

	useEffect( () => {
		const path = location.pathname;

		const index = items.findIndex( ( item ) => {
			if ( item.item === 'settings' && path === '/' ) return true;
			return path.includes( item.item );
		} );
		setActiveItemIndex( index === -1 ? 0 : index );
	}, [ location.pathname ] );

	return (
		<RefactHeader
		logoSource={ 'Refact' }
		items={ items }
		onSelectItem={ handleSelectItem }
		activeItemIndex={ activeItemIndex }
	/>
		
	);
};

export default Header;
