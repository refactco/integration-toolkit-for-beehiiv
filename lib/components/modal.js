/* eslint-disable import/no-extraneous-dependencies */
import { memo } from 'react';
import Backdrop from './backdrop';
import styled from 'styled-components';

const Modal = ( { show, modalClosed, children } ) => {
	return (
		<>
			<Backdrop show={ show } modalClosed={ modalClosed } />
			<ModalContainer
				className="Modal"
				style={ {
					transform: show ? 'translateX(0)' : 'translateX(-100vw)',
					opacity: show ? '1' : '0',
				} }
			>
				{ children }
			</ModalContainer>
		</>
	);
};
const ModalContainer = styled.div`
	position: fixed;
	z-index: 500;
	background-color: white;
	width: 85%;
	border: 1px solid #ccc;
	border-radius: 5px;
	box-shadow: 1px 1px 1px #eee;
	padding: 16px;
	left: 5%;
	top: 20%;
	box-sizing: border-box;
	transition: all 0.3s ease-out;
	overflow-y: scroll;

	@media ( min-width: 780px ) {
		width: 500px;
		left: calc( 50% - 250px );
		top: 20%;
		overflow-y: hidden;
	}
`;

export default memo( Modal );
