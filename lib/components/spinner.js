import styled, { keyframes } from 'styled-components';

const Spinner = ( { label, marginBottom } ) => (
	<SpinnerContainer>
		<Loader marginBottom={ marginBottom } />
		{ label && <Label>{ label }</Label> }
	</SpinnerContainer>
);

const spin = keyframes`
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
`;

const SpinnerContainer = styled.div`
	display: flex;
	justify-content: center;
	align-items: center;
	position: absolute;
	flex-direction: column;
	gap: 8px;
	top: 50%;
	left: 50%;
	transform: translate( -50%, -50% );
`;

const Loader = styled.div`
	border: 4px solid rgba( 0, 0, 0, 0.1 );
	border-top: 4px solid #2e9e62;
	border-radius: 50%;
	width: 40px;
	height: 40px;
	margin-bottom: ${ ( props ) => props.marginBottom ?? '16px' };
	animation: ${ spin } 1s linear infinite;
`;

const Label = styled.p`
	color: #fff;
	font-size: 16px;
`;

export default Spinner;
