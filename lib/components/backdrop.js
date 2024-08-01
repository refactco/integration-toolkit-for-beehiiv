import styled from 'styled-components';

const Backdrop = ( { show, modalClosed, children } ) =>
	show ? (
		<BackdropContainer onClick={ modalClosed }>
			{ children }
		</BackdropContainer>
	) : null;

const BackdropContainer = styled.div`
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba( 0, 0, 0, 0.5 );
	z-index: 100;
	display: flex;
	justify-content: center;
	align-items: center;
`;

export default Backdrop;
