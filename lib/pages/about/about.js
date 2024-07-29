/* eslint-disable react/no-unescaped-entities */

import { Section } from '@refactco/ui-kit';
import { Link } from 'react-router-dom';
const About = () => {
	return (
		<Section
			headerProps={ {
				title: 'Refact.co',
				description: 'Product Design Studio for Audience-first Media',
			} }
		>
			<p>
				We help digital newsrooms and publication media design scalable
				publishing platforms, collect and make sense of their data, and
				optimize their audience's experience.
			</p>
			<Link to={ 'https://refact.co' }>Refact.co</Link>
		</Section>
	);
};

export default About;
