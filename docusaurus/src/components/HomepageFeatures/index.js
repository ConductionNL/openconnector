import React from 'react';
import clsx from 'clsx';
import styles from './styles.module.css';

const FeatureList = [
  {
    title: 'API Gateway & Mapping',
    description: (
      <>
        Connect to any external API — REST, SOAP, or XML. Transform and map data between formats using Twig templates and flexible mapping rules.
      </>
    ),
  },
  {
    title: 'Data Synchronization',
    description: (
      <>
        Keep data in sync between Nextcloud and external systems. Scheduled jobs, webhooks, and event-driven processing with full audit logging.
      </>
    ),
  },
  {
    title: 'Enterprise Service Bus',
    description: (
      <>
        Route, transform, and orchestrate API calls across your organization. OAuth, JWT, and ZGW authentication built in.
      </>
    ),
  },
];

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
