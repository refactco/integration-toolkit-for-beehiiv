/* eslint-disable  jsx-a11y/click-events-have-key-events */
/* eslint-disable  jsx-a11y/no-static-element-interactions */
import { Progress, Button, ButtonColor } from '@refactco/ui-kit';
import styled from 'styled-components';

const ImportPercentage = ( {
	progressValue,
	jobAction,
	jobActionHandler,
	loadingJobAction,
	scheduleId,
} ) => {
	const handleClick = () => {
		const url = new URL( window.location.href );
		url.hash = `#/import-campaigns/scheduled?scheduleId=${ scheduleId }`;
		window.open( url.toString(), '_blank' );
	};

	return (
		<>
			{ scheduleId && (
				<p>
					Click on the following link to get full information about
					the scheduled task with this ID:
					<span className="span-link" onClick={ handleClick }>
						{ ' ' }
						{ scheduleId }
					</span>
				</p>
			) }

			<h3>{ progressValue } %</h3>
			<Progress value={ progressValue } max="100" />
			<ButtonContainer>
				<Button
					color={ ButtonColor.GREEN }
					onClick={ () => jobActionHandler( jobAction ) }
					disabled={ loadingJobAction }
				>
					{ jobAction === 'pause' ? 'Pause' : 'Resume' }
				</Button>
				<Button
					color={ ButtonColor.RED }
					onClick={ () => jobActionHandler( 'cancel' ) }
					disabled={ loadingJobAction }
				>
					Cancel
				</Button>
			</ButtonContainer>
		</>
	);
};

const ButtonContainer = styled.div`
	display: flex;
	gap: 16px;
	margin-top: 20px;
`;

export default ImportPercentage;
