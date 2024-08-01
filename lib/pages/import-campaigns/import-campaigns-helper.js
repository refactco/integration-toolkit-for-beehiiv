export const tabs = [
	{
		name: 'manual',
		title: 'Import Setting',
		route: '/import-campaigns/manual',
	},
	{
		name: 'scheduled',
		title: 'Scheduled Import',
		route: '/import-campaigns/scheduled',
	},
];

export function importCampaignsHelper( state, setState, navigate ) {
	function handleTabClick( tabIndex ) {
		setState( {
			...state,
			activeIndex: tabIndex,
		} );
		navigate( tabs[ tabIndex ].route );
	}

	return {
		handleTabClick,
	};
}
