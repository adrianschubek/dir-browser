import React from "react";
import clsx from "clsx";
import styles from "./styles.module.css";
import Checkmark from "../Checkmark";

export default function HomepageFeatures(): JSX.Element {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/docker.webp"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Easy Docker deployment
                <Checkmark />
              </h3>
              <p>
                Application is self-contained in a single image and ready to be
                deployed.
              </p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/counter.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Download Counter
                <Checkmark />
              </h3>
              <p>
                Tracks the number of times a file has been downloaded and stores
                it in a <i>Redis</i> database.
              </p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/metadata.png"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Custom description & labels
                <Checkmark />
              </h3>
              <p>
                Display additional information about your files & folders with custom metadata.
              </p>
            </div>
          </div>
        </div>
        <div className="row">
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/readme.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                README Markdown Rendering
                <Checkmark />
              </h3>
              <p>Automatically renders Markdown README files.</p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/colorp.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Themes & Darkmode
                <Checkmark />
              </h3>
              <p>
                Choose from many different <b>themes</b> with <b>darkmode</b>{" "}
                support.
              </p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img
                style={{
                  maxHeight: "100px",
                  maxWidth: "150px",
                  paddingBottom: "2em",
                  paddingTop: "2em",
                }}
                src="/img/nginx.svg"
              ></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Fast file serving
                <Checkmark />
              </h3>
              <p>
                NGINX, a high performance reverse proxy, handles all file
                serving to maximize performance.
              </p>
            </div>
          </div>
        </div>
        <div className="row">
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/secure.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Secure by default
                <Checkmark />
              </h3>
              <p>
                Strict <b>Read-only</b> access to files and folders.
                Additionaly protect your files with a <b>password</b>.
              </p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/ignore.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Hide files & folders
                <Checkmark />
              </h3>
              <p>
                Want to hide your dot files? Hide old documents? You can do it.
              </p>
            </div>
          </div>
          <div className={clsx("col col--4")}>
            <div className="text--center">
              <img style={{ maxHeight: "100px" }} src="/img/settings.svg"></img>
            </div>
            <div className="text--center padding-horiz--md">
              <h3>
                Highly configurable
                <Checkmark />
              </h3>
              <p>
                <b>Customize</b> the application to your needs by using
                environment variables.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
