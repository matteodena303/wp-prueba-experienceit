## WordPress Technical Test – User Listing with AJAX

This project is a WordPress site that implements a custom plugin to display a paginated list of users with live search via AJAX. It simulates an external API using a local PHP method and does not store user data in the database. The plugin exposes a shortcode ([ult_user_list]) that can be added to any page or post to render the list.

## Requirements

• Docker and Docker Compose installed on your system.

## Installation and Setup

1. Clone the repository
   git clone https://github.com/matteodena303/wp-prueba-experienceit.git
   cd wp-prueba-experienceit

2. Start the Docker environment
   The repository includes a Docker Compose configuration that sets up WordPress, a MySQL database and web server. Build and start the containers with:
   docker compose up -d --build

3. Import the provided database (optional)
   If you want to load the sample database provided in the /BBDD folder, run:
   docker exec -i wp_prueba_db mysql -uroot -proot wordpress < BBDD/db.sql
   This step is only necessary if the database has not been initialised yet.

4. Access the WordPress site
   Navigate to http://localhost:8090 in your browser. You should see the default WordPress homepage.
   Admin Panel

To access the WordPress admin panel, use the following credentials:
• Username: admin
• Password: Admin-prueba303

Once logged in, you can create or edit pages and embed the user listing via the shortcode.
Using the User Listing Shortcode

The custom plugin registers the shortcode:
[ult_user_list]

Add this shortcode into any page or post to display the paginated and searchable user list. A demo page is already included in the installation.

## Features

• Pagination: The list shows five users per page. Use the navigation buttons to move between pages.
• Live Search: Filters by Name, Surnames and Email are applied instantly without reloading the page. The search fields use debounce to avoid excessive AJAX calls.
• Simulated API: The plugin does not store users in the database. Instead, it generates 50 sample users and filters them server‑side to mimic an external API call.
• No Page Reloads: Both the search and pagination operate via AJAX, so the page content is updated dynamically.
Project Structure
• wp-content/plugins/user-listing-test/ – Contains the custom plugin files:
• user-listing-test.php – Main plugin file (now documented in English). Handles shortcode registration, AJAX endpoints, filtering and table rendering.
• assets/user-listing.js – JavaScript for live search and pagination (comments translated to English).
• BBDD/db.sql – SQL dump for the sample database.
• docker-compose.yml – Docker configuration for WordPress and MySQL.
• Other standard WordPress core files and themes.

## Technical Notes

- The user list is generated server-side to simulate an external API response.
- All filtering and pagination logic is handled via AJAX.
- No WordPress views or templates are used for rendering the list.
- The solution is implemented as a custom plugin following WordPress best practices.

## Notes

• The provided db.sql is optional; the plugin works without any user data because it generates sample users on the fly.

---
