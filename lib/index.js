import { StyledWordpressComponent } from '@refactco/ui-kit';
import { createRoot } from '@wordpress/element';
import Home from './pages/home/home';

document.addEventListener( 'DOMContentLoaded', () => {
	const element = document.getElementById(
		'integration-toolkit-for-beehiiv-app'
	);
	const root = createRoot( element );
	if ( element ) {
		root.render(
			<>
				<StyledWordpressComponent />
				<Home />
			</>
		);
	}
} );
