# Pies-WordPress-Plugin
# Pies Custom Post Type Plugin Documentation

## 1. Installation and Activation Process

1. **Download the Plugin:**
   - Download the plugin ZIP file from the GitHub repository.

2. **Upload the Plugin:**
   - Navigate to `Plugins > Add New` in the WordPress dashboard.
   - Click on `Upload Plugin`, choose the ZIP file, and click `Install Now`.

3. **Activate the Plugin:**
   - After installation, click `Activate Plugin` to start using the plugin.

## 2. Using the Shortcode

The plugin provides a shortcode `[pies]` that you can use to display pies on your site.

**Basic Usage:**
- To display all pies, simply add the shortcode `[pies]` to any post, page, or widget.

**Filtered by Pie Type (`lookup` attribute):**
- Use the `lookup` attribute to filter pies by their type. For example:
  ```[pies lookup="apple"]```
  This will display only pies with the type "apple".

**Filtered by Ingredients (`ingredients` attribute):**
- Use the `ingredients` attribute to filter pies by their ingredients. For example:
  ```[pies ingredients="sugar"]```
  This will display pies that contain "sugar" as an ingredient.

**Combined Filters:**
- You can combine the `lookup` and `ingredients` attributes. For example:
  ```[pies lookup="apple" ingredients="sugar"]```
  This will display apple pies that contain sugar.

## 3. Optional Features

**Pagination:**
- The shortcode automatically handles pagination. It will display up to 5 pies per page with pagination links at the bottom. You can navigate between pages to view more pies.

## 4. Object-Oriented Programming (OOP) Approach

**Class Structure:**
- The plugin is encapsulated within a single class, `Pies_Custom_Post_Type`, which is responsible for registering the custom post type, adding meta boxes, handling saving of meta data, and rendering the shortcode.

**Design Choices:**
- **Modularity:** Each piece of functionality (e.g., custom post type registration, meta box creation, shortcode handling) is separated into methods within the class. This makes the code easier to maintain and extend.
- **Hooks:** The plugin makes use of WordPress hooks (`add_action`, `add_shortcode`, etc.) to ensure that the functionality is executed at the correct time in the WordPress lifecycle.
- **Security:** Nonces are used to secure the saving of meta data, preventing unauthorized changes to pie posts.
