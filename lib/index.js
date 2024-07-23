import { render } from '@wordpress/element';
import ImportCampaigns from './import-campaigns';

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('integration-toolkit-for-beehiiv-app');
    if (element) {
        render(<ImportCampaigns />, element);
    }
});
