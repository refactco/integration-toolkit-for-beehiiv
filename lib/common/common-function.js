export function logger( message, ...optionalParams ) {
	const { log } = console;

	if ( optionalParams.length > 0 ) {
		log( message, optionalParams );
	} else {
		log( message );
	}
}

export async function delay( ms ) {
	return new Promise( ( resolve ) => {
		setTimeout( () => {
			resolve();
		}, ms );
	} );
}
