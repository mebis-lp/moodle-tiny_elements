# Elements

## About the plugin

A plugin for the Moodle TinyMCE editor providing customizable components for structuring learning content. It contains sets of visual components designed explicitly for learning
* from componentsforlearning.org (by Roger Segú) and 
* teaching avatars, work modes, nature and culture by Dunja Speckner.

No configuration settings are required for this plugin. Just install it from the Site Administration area (Plugins → Install plugins → Install plugin from ZIP file). 

Once the plugin is installed, a button and a menu item will be visible in the TinyMCE editor. There is one setting to preview the components when hover the mouse cursor over each component, it is enabled by default, to change it modify enablepreview setting on Site Administration area → Plugins → Text Editors → TinyMCE editor → Elements.

The capability 'tiny/elements:viewplugin' allows to configure the plugin visibility to any role.

This plugin is a fork of tiny_elements https://moodle.org/plugins/tiny_elements) by Marc Català and Roger Segú and also based on their work on atto_elements (https://moodle.org/plugins/atto_elements). These are part of a broader, collaborative project called Components for Learning (https://componentsforlearning.org). You will find there all the related documentation and detailed usage recommendations and examples for the elements set of components included here.

Icons authored by Roger Segú, except for the following, licensed under Creative Commons CCBY, [Glasses](https://thenounproject.com/icon/70907/) by Austin Condiff, [Estimate](https://thenounproject.com/icon/1061038/) by xwoodhillx, [Quote](https://thenounproject.com/icon/77920/) by Rohith M S, [Pin](https://thenounproject.com/icon/689105/) by Icons fest, [Bulb](https://thenounproject.com/icon/1175583/) by Adrien Coquet, [Date](https://thenounproject.com/icon/1272092/) by Karan, [Success](https://thenounproject.com/icon/3405499/) by Alice Design, [Clock](https://thenounproject.com/icon/2310543/) by Aybige, [Feedback](https://thenounproject.com/icon/651868/) by dilayorganci, [Star](https://thenounproject.com/icon/1368720/) by Zaff Studio, [Tag](https://thenounproject.com/icon/938953/) by Ananth.

## How to use the plugin

### Recommended additional plugin

Editing CSS / HTML code in a textarea is really a pain. Because of that we urgently recommend to install the editor_codemirror plugin (https://github.com/mebis-lp/moodle-editor_codemirror) that is automatically used for editing in tiny_elements management interface then. It helps you with syntax highlighting and code completion.

### Structure of plugin data

The main elements of the plugin are **components** (consisting of HTML and CSS code). When users click on them, the corresponding HTML code is inserted into the content area of TinyMCE.

Every component belongs to exactly one **category**. Each category has some CSS code that is common to all components of the category and some files for the category.

A category can have multiple **flavors**. Every flavor can also have some CSS code and can be connected to components of the category. Flavors can provide different sub-styles and can be also used as kind of sub-categories.

Components can also have up to three **variants** that can be switched on/off (multiple variants can be switched on at the same time). Variants have CSS and HTML code.

### Base templates (delivered with the plugin)

The plugin has some templates in db/base.zip. They can be imported after installation - feel free to use or to ignore them.

### Naming

Every category, component, flavor and variant needs a unique name. Please use lowercase names without spaces and special characters other than "-" and "_" (these names are used as part of CSS class names) and keep in mind, that these names might conflict with existing CSS class names or other existing custom components / categories / ...

Categories and components will be prefixed by "elements-", flavors and variants written as "elements-..-flavor" / "elements-..-variant" where ".." is the name of the flavor / variant.

Example for naming:

Your project is called "guinea pig", so you could call 
* your category "guineapig"
* your components "guineapig-title", "guineapig-card", ...
* your flavors "guineapig-with-hat", "guineapig-with-face-mask", ...
* your variants "guineapig-black-white", "guineapig-with-background", ...

Especially if you want to share your templates with others, it is particularly important to be very careful when chosing names.

### Creating categories, components, flavors and variants

To create custom components, go to https://yourmoodle/lib/editor/tiny/plugins/elements/management.php

#### Categories

You will need to create at least one category for your components. 

You can enter the following data for your category:

Name: The internal name (not displayed to the users), should be also used as part of the CSS class name (see "Naming" above).
Display name: The name displayed to the users.
Display order: Categories will be sorted by this number when they are presented to the users (ascending order).
CSS: CSS code that is common for all parts of the category.
Files: Upload the files you want to use in your category (this will mainly be images, maybe fonts too). For images we recommend to use SVG if possible. Do not use files from other categories in your components as this might lead to a lot of problems, especially when doing import / export.

#### Components

When creating a component you can enter the following data:

Name: The internal name (not displayed to the users), should be also used as part of the CSS class name (see "Naming" above).
Display name: The name displayed to the users.
Category: The category the component belongs to.
Code: The HTML code that is inserted to the TinyMCE content area. This can contain the following placeholders:
* {{PLACEHOLDER}} - this is replaced by the currently selected text or (if nothing is selected) a dummy text
* {{CATEGORY}} - the name of the category, prefixed by "elements-"
* {{COMPONENT}} - the name of the component, prefixed by "elements-"
* {{FLAVOR}} - "elements-..-flavor" where ".." is the name of the chosen flavor (is empty, if none is chosen)
* {{VARIANTS}} - the space separated names of the activated variants in the format "elements-..-variant" where ".." is the name of the variant
* {{VARIANTSHTML}} - the concatenated HTML code for all activated variants

Text: The default dummy text for {{PLACEHOLDER}}
Variants: Choose the variants available for this component (you can also add them later)
Flavors: The flavors available for this component (you can also add them later)
Display order: Categories will be sorted by this number when they are presented to the users (ascending order).
CSS: CSS code that is used for this component.
JS: Leave this empty for now - Javascript is not implemented in a reliable way yet.
Icon URL: URL of an image that should be shown on the button for the component. To use an image uploaded to the category, click to "Show urls to symbols" below and copy the URL from there (will be like "https://yourmoodle/pluginfile.php/1/tiny_elements/images/..."). Please choose only images from your category.
Hide for students: Check this to show this component only to teachers.

#### Flavors

You don't need to have flavors. If you create a flavor, you can enter the following data:

Name: The internal name (not displayed to the users), will be part of the CSS class name ("elements-..-flavor", see "Naming" above).
Display name: The name displayed to the users.
Category: The category the flavor belongs to.
Display order: Flavors will be sorted by this number when they are presented to the users (ascending order).
CSS: CSS code that is used for this flavor.
Hide for students: Check this to show this flavor only to teachers.

In addition (after saving), the button icons for components can be changed depending on the flavor. To choose the icons depending on flavor, go to the flavor and click on the rightmost button there. Then you can enter image URLs.

#### Variants

You don't need to have variants. If you create a variant, you can enter the following data:

Name: The internal name (not displayed to the users), will be part of the CSS class name ("elements-..-variant", see "Naming" above).
Display name: The name displayed to the users.
Category: The category the flavor belongs to.
Content: HTML code that is added to the {{VARIANTSHTML}} placeholder.
CSS: CSS code that is used for this variant. 
Icon URL: Icon that is displayed on the variant button. It is recommended to use monochromatic images. To use an image uploaded to the category, click to "Show urls to symbols" below and copy the URL from there (will be like "https://yourmoodle/pluginfile.php/1/tiny_elements/images/..."). Please choose only images from your category.
Checkbox for C4L compatibility: Do not use that.

### Export

You can either export your complete dataset or one single category.

The export file is a ZIP file and includes all files in subfolders named like the categories and one XML file that contains the categories, components, flavors and variants.

This file can be imported at every tiny_elements instance.

### Import

You can import any export ZIP file. When you want to import a file, you can simulate the import first to see, what would happen (this is a dry run not changing any data).

An import replaces existing categories, components, flavors and variants depending on their name (e.g. if you have an existing component "dark-box" and you import a component that is also named "dark-box", the imported one will overwrite the existing one). There is no way to undo an import, so be very careful!
