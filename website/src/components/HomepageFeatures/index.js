import React from 'react';
import clsx from 'clsx';
import styles from './styles.module.css';

/**
 * List of features displayed on the homepage
 * Each feature has a title and description highlighting key capabilities
 * of the Zaakafhandelapp case management system
 */
const FeatureList = [
  {
    title: 'Efficient Case Management',
    description: (
      <>
        Streamline your municipal case handling with an intuitive interface that helps track, process and manage cases efficiently while ensuring compliance with Dutch administrative law.
      </>
    ),
  },
  {
    title: 'Standards-Based Architecture',
    description: (
      <>
        Built on Dutch government standards like ZGW APIs and Common Ground principles, ensuring interoperability and future-proof case management for municipalities.
      </>
    ),
  },
  {
    title: 'Complete Process Control',
    description: (
      <>
        Manage the entire case lifecycle from intake to archiving, with built-in support for documents, tasks, and communications while maintaining full audit trails.
      </>
    ),
  },
];

/**
 * Component to render a single feature
 * @param {string} title - The title of the feature
 * @param {JSX.Element} description - The description of the feature
 * @returns {JSX.Element} Feature component
 */
function Feature({title, description}) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <h3>{title}</h3>
        <p>{description}</p>
      </div>
    </div>
  );
}

/**
 * Main component that displays all features on the homepage
 * Renders the features in a responsive grid layout
 * @returns {JSX.Element} HomepageFeatures component
 */
export default function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}