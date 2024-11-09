# Widget Usage Tracker for Elementor

**Widget Usage Tracker for Elementor** is a powerful WordPress plugin that allows you to monitor and analyze the usage of Elementor widgets across your website. With this plugin, you can easily see which widgets are most frequently used and identify the specific pages or posts where they appear.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
  - [Prerequisites](#prerequisites)
  - [Steps](#steps)
- [Dependencies](#dependencies)
- [Usage](#usage)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Features

- **Comprehensive Tracking:** Automatically tracks all registered Elementor widgets and displays their usage counts.
- **Detailed Insights:** View detailed information about where each widget is used, including links to the specific pages or posts.
- **User-Friendly Interface:** Intuitive admin interface with sortable tables and interactive modals for easy navigation.
- **Automatic Updates:** Integrated update checker ensures your plugin stays up-to-date with the latest features and security patches.
- **Localization Ready:** Fully translatable, allowing you to use the plugin in your preferred language.
- **Database Optimization:** Efficiently stores widget usage data in custom tables for quick access and minimal performance impact.

## Installation

### Prerequisites

- **WordPress:** Version 5.0 or higher.
- **Elementor:** Version 2.0 or higher.

### Steps

1. **Download the Plugin:**

    - Clone the repository:

        ```bash
        git clone https://github.com/robertdevore/widget-usage-tracker-for-elementor.git
        ```

    - Or download the ZIP file from the [GitHub repository](https://github.com/robertdevore/widget-usage-tracker-for-elementor/).

2. **Upload to WordPress:**

    - **Via FTP:**
        - Upload the `widget-usage-tracker-for-elementor` folder to the `/wp-content/plugins/` directory.
    - **Or Via the WordPress Admin Dashboard:**
        - Navigate to **Plugins > Add New**.
        - Click on **Upload Plugin**.
        - Choose the downloaded ZIP file and click **Install Now**.

3. **Install Dependencies:**

    - This plugin uses Composer to manage dependencies. Ensure you have [Composer](https://getcomposer.org/) installed.
    - Navigate to the plugin directory and install dependencies:

        ```bash
        cd wp-content/plugins/widget-usage-tracker-for-elementor
        composer install
        ```

4. **Activate the Plugin:**

    - Go to **Plugins > Installed Plugins** in your WordPress dashboard.
    - Locate **Widget Usage Tracker for Elementor** and click **Activate**.

5. **Initial Setup:**

    - Upon activation, the plugin will create two custom database tables:
        - `wut_widget_usage_counts`: Stores the usage count for each widget.
        - `wut_widget_usage_posts`: Stores the association between widgets and post IDs where they are used.
    - A scheduled cron event will be set up to update widget usage counts hourly. The plugin also triggers an immediate update upon activation.

## Dependencies

**Widget Usage Tracker for Elementor** requires either **Elementor** or **Elementor Pro** to be installed and active on your WordPress site. If neither is active, the plugin will automatically deactivate and display an admin notice informing you of the missing dependencies.

### Installing Elementor

1. **Via WordPress Admin Dashboard:**
   - Navigate to **Plugins > Add New**.
   - Search for **Elementor**.
   - Click **Install Now** and then **Activate**.

2. **Manually:**
   - Download the plugin from the [Elementor website](https://elementor.com/).
   - Upload it to your `/wp-content/plugins/` directory.
   - Activate it through the **Plugins** menu in WordPress.

### Installing Elementor Pro (Optional)

If you prefer to use **Elementor Pro** for additional features:

1. Purchase **Elementor Pro** from the [official website](https://elementor.com/pro/).
2. Download the **Elementor Pro** plugin.
3. Upload it to your `/wp-content/plugins/` directory.
4. Activate it through the **Plugins** menu in WordPress.
5. Enter your license key to receive updates and support.

## Usage

1. **Accessing the Tracker:**

    - After activation, navigate to **Dashboard > Widget Tracker** in your WordPress admin menu.

2. **Viewing Widget Usage:**

    - The main page displays a table listing all registered Elementor widgets along with their usage counts.
    - Click on the **View Details** link for any widget to open a modal that shows the specific pages or posts where the widget is used.

3. **Understanding the Data:**

    - **Widget Type:** The name of the Elementor widget.
    - **Usage Count:** The number of times the widget is used across the site.
    - **Details:** A link to view detailed information about where the widget is used.

4. **Interacting with the Modal:**

    - The modal provides a list of pages or posts containing the selected widget.
    - Click on any link within the modal to navigate directly to the content where the widget is implemented.

## Frequently Asked Questions

### Does this plugin affect site performance?

The plugin is optimized for performance and should have minimal impact on your site's speed. It primarily runs queries in the admin area and does not affect the front-end performance.

### Is my data safe?

Yes, the plugin follows WordPress coding standards and best practices to ensure data security. It sanitizes all inputs and uses prepared statements for database queries.

### Can I customize the plugin?

Absolutely! The plugin is open-source and fully customizable. Feel free to fork the repository and modify it to suit your specific needs.

### What happens when I uninstall the plugin?

Upon uninstallation, the plugin will clean up by removing its custom database tables (`wut_widget_usage_counts` and `wut_widget_usage_posts`) and clearing any scheduled cron events related to widget usage tracking.

## Contributing

Contributions are welcome! Whether it's reporting a bug, suggesting a feature, or submitting a pull request, your input is valuable.

1. **Fork the Repository:**

    - Click on the **Fork** button at the top right of the repository page.

2. **Create a Branch:**

    ```bash
    git checkout -b feature/your-feature-name
    ```

3. **Make Your Changes:**

    - Commit your changes with clear and concise messages.

4. **Push to Your Fork:**

    ```bash
    git push origin feature/your-feature-name
    ```

5. **Submit a Pull Request:**

    - Navigate to your fork on GitHub and click **New Pull Request**.

Please ensure that your code follows the WordPress Coding Standards and includes appropriate documentation and comments.

## License

This plugin is licensed under the GNU General Public License v2.0 or later.

## Support

If you encounter any issues or have questions, please open an [issue](https://github.com/robertdevore/widget-usage-tracker-for-elementor/issues) on the GitHub repository. For additional support, you can contact me at [robertdevore.com](https://robertdevore.com/).