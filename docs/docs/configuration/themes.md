---
sidebar_position: 0
---

import Tabs from "@theme/Tabs";
import TabItem from "@theme/TabItem";
import Image from "@theme/IdealImage";

# Themes

Choose from a variety of themes to customize the look and feel of your directory browser. All themes support light and dark mode.

:::info
You can change the theme by setting the `THEME` environment variable to one of the following values:

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e THEME=sketchy -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```
:::

:::tip
Right-click on the images and select "Open image in new tab" to see the full size.
:::

## `cosmo` (Default)

<Tabs>
  <TabItem value="light" label="Light" default>
    <Image img={require("../../static/img/cosmo_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/cosmo_dark.png")} />
  </TabItem>
</Tabs>

## `litera`

<Tabs>
  <TabItem value="light" label="Light" default>
    <Image img={require("../../static/img/litera_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/litera_dark.png")} />
  </TabItem>
</Tabs>

## `cerulean`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/cerulean_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/cerulean_dark.png")} />
  </TabItem>
</Tabs>

## `materia`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/materia_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/materia_dark.png")} />
  </TabItem>
</Tabs>

## `quartz`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/quartz_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/quartz_dark.png")} />
  </TabItem>
</Tabs>

## `sandstone`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/sandstone_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/sandstone_dark.png")} />
  </TabItem>
</Tabs>

## `sketchy`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/sketchy_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/sketchy_dark.png")} />
  </TabItem>
</Tabs>

## `united`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/united_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/united_dark.png")} />
  </TabItem>
</Tabs>

## `yeti`

<Tabs>
  <TabItem value="light" label="Light">
    <Image img={require("../../static/img/yeti_light.png")} />
  </TabItem>
  <TabItem value="dark" label="Dark">
    <Image img={require("../../static/img/yeti_dark.png")} />
  </TabItem>
</Tabs>