/* eslint-disable react-hooks/exhaustive-deps */
const { useState } = wp.element;
const apiFetch = wp.apiFetch;
import {
    Section,
    Button,
    ButtonSize,
    ButtonColor,
    Icon,
    IconName,
    IconSize,
    Alert,
    AlertStatus,
} from '@refactco/ui-kit';
import { styled } from 'styled-components';

const ImportCampaigns = () => {
    const [ isImporting, setIsImporting ] = useState( false );
    const [ errorMessage, setErrorMessage ] = useState( '' );
    const [ successMessage, setSuccessMessage ] = useState( '' );

    const setMessage = ( error = '', success = '' ) => {
        setErrorMessage( error );
        setSuccessMessage( success );
    };

    const importCampaigns = async () => {
        setIsImporting( true );
        try {
            await apiFetch( {
                path: '/itfb/v1/import-campaigns',
                method: 'POST',
                data: {
                    credentials: JSON.stringify({
                        // api_key: 'CssYT1DeGrawMirevhOuGmbouVTl3OCpB6x49HcHoXzxKyTRGfkXCBvPwydmSAxU',
                        api_key: 't7e2Dr2KERFfT6CxWbWMMsoTjCt6jQD1cDcJlpbgIxGqCYGGJXpxAP353aHoo8JD',
                        // publication_id: 'pub_871acc85-c29d-4b83-ab89-98405d227f0e',
                        publication_id: 'pub_0294c0e8-dd51-41e4-a635-daf2fae1c15f',
                    }),
                    post_type: 'post',
                    taxonomy: 'category',
                    taxonomy_term: 24,
                    author: 8,
                    import_cm_tags_as: 'post_tag',
                    import_option: 'new',
                    schedule_settings: JSON.stringify(
                        {
                            enabled: 'on',
                            frequency: 'weekly',
                            specific_day: 'monday',
                            time: '23:23',
                        }
                    ),
                    post_status: JSON.stringify( {
                        // draft: 'draft',
                        confirmed: 'publish',
                    } ),
                    audience: 'free',
                },
            } );
            setMessage( '', 'Campaigns imported successfully.' );
        } catch ( err ) {
            setMessage( 'Error importing campaigns. Please try again.' );
        } finally {
            setIsImporting( false );
        }
    };

    return (
        <>
            <Section
                headerProps={ {
                    title: 'Import Campaigns',
                    description: 'Click the button below to import campaigns.',
                } }
            >
                { errorMessage && (
                    <StyledAlert status={ AlertStatus.ERROR }>
                        { errorMessage }
                    </StyledAlert>
                ) }
                { successMessage && (
                    <StyledAlert status={ AlertStatus.SUCCESS }>
                        { successMessage }
                    </StyledAlert>
                ) }
                <ButtonContainer>
                    <Button
                        onClick={ importCampaigns }
                        disabled={ isImporting }
                        size={ ButtonSize.SMALL }
                        color={ ButtonColor.BLUE }
                        icon={
                            <Icon
                                iconName={ IconName.IMPORT_EXPORT }
                                size={ IconSize.TINY }
                            />
                        }
                    >
                        { isImporting ? 'Importing...' : 'Import Campaigns' }
                    </Button>
                </ButtonContainer>
            </Section>
        </>
    );
};


const ButtonContainer = styled.div`
    display: flex;
    justify-content: center;
    margin-top: 2rem;
`;

const StyledAlert = styled(Alert)`
    margin-bottom: 20px;
`;

export default ImportCampaigns;