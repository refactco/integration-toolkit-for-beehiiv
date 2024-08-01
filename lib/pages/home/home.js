import { HashRouter, Route, Routes } from 'react-router-dom';

import Layout from '../../components/layout';
import Header from '../../components/header';
// use react-router-dom to create routes
import About from '../about/about';
import ImportCampaigns from '../import-campaigns/import-campaigns';
import { ToastContainer } from 'react-toastify';

const Home = () => {
	return (
		<HashRouter>
			<Layout>
				<Header />
				<ToastContainer position="bottom-right" />
				<Routes>
					<Route path="/" element={ <ImportCampaigns /> } />
					<Route path="/about" element={ <About /> } />
					<Route
						path="/import-campaigns/*"
						element={ <ImportCampaigns /> }
					/>
				</Routes>
			</Layout>
		</HashRouter>
	);
};

export default Home;
