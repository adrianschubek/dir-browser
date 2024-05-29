import React from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import HomepageFeatures from '@site/src/components/HomepageFeatures';

import styles from './index.module.css';

import CodeBlock from '@theme/CodeBlock';

function HomepageHeader() {
  const { siteConfig } = useDocusaurusContext();
  return (
    <header /* style={{background: "#2e4385"}} */ className={clsx('hero hero--white bg-1', styles.heroBanner)}>
      {/* <div className="container">
        <h1 className="hero__title">{siteConfig.title}</h1>
        <p className="hero__subtitle">{siteConfig.tagline}</p>
        <div className={styles.buttons}>
          <Link
            className="button button--secondary button--lg"
            to="/intro">
            Docusaurus Tutorial - 5min ⏱️
          </Link>
        </div>
      </div> */}

      <div className="container" style={{color:"white"}}>
        <div className="row">
          <div className="col col--6">
            <img style={{ boxShadow: "0px 0px 10px 0px #8585857d" }} src="/img/z1.png" />
          </div>
          <div className="col col--6" style={{ margin: "auto" }}>
            <h1 className="hero__title">{siteConfig.title}</h1>
            <p className="hero__subtitle">Browse your files and folders on the web</p>
            <p>Directory Listing in a single Docker Image</p>
            <div className={styles.buttons} style={{marginBottom: "1em"}}>
              <Link
                className="button button--info button--outline button--lg"
                style={{ marginRight: "1em", color: "white" }}
                to="https://dir-demo.adriansoftware.de">
                Demo
              </Link>
              <Link
                className="button button--secondary button--lg"
                to="/v2/intro">
                Get started 🚀
              </Link>
            </div>

            <CodeBlock className="xxx">
            docker run --rm -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/ adrianschubek/dir-browser
            </CodeBlock>

          </div>
        </div>
      </div>
    </header>
  );
}

function Section2() {
  return <div className=" xxx" style={{ background: "var(--ifm-color-gray-900)", padding: "3em", paddingTop: "3em", paddingBottom: "3em" }}>
    <h1 className="hero__title" style={{ color: "var(--ifm-color-gray-700)", marginLeft: "auto" }}>🌙 Darkmode</h1>
    <img /* style={{ boxShadow: "0px 0px 10px 0px #8585857d" }} */ src="/img/z2.png" />
  </div>
}

function Section3() {
  return <Link
    to="https://utpp.adriansoftware.de"> <div className=" " style={{
      textAlign: "center", background: "#2b70b6", textAlign: "center",
      display: "flex",
      alignItems: "center",
      flexDirection: "row",
      justifyContent: "center", /* "var(--ifm-color-gray-100)" */
    }}>

      <p className="hero__subtitle" style={{ color: "var(--ifm-color-gray-400)" }}>Powered by </p>
      <img /* style={{ boxShadow: "0px 0px 10px 0px #8585857d" }} */ height={130} src="/img/utpp.png" />
    </div>
  </Link>
}

export default function Home() {
  const { siteConfig } = useDocusaurusContext();

  return (
    <Layout
      title={`${siteConfig.title}`}
      description="dir-browser">
      <HomepageHeader />
      {/*  <div className="c">
        <svg style={{
          fill: "#2e4385", position: "relative", display: "block", marginTop: "-1px", width: "100%", height: "25px",
        }} data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
          <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25"></path>
          <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" ></path>
          <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
        </svg>
      </div> */}
      <main>
        <HomepageFeatures />
        <Section2 />
        <Section3 />
      </main>
    </Layout >
  );
}
