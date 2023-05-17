import { render } from "@wordpress/element";

import "./components/settings.css";
import Header from "./components/header";
import Tabs from "./components/tabs";
const Settings = (props) => {

  return (
    <div className="re-beehiiv-settings-wrap">
      <Header />
      <Tabs />
    </div>
  );
};

var rootElement = document.getElementById("re-beehiiv-settings");

if (rootElement) {
  render(<Settings scope="global" />, rootElement);
}