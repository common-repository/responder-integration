<?php
defined("ABSPATH") || exit();
function woosponder_fetch_active_connections()
{
    // Start the main container with a specific class for the active connections tab
    echo '<div class="woosponder-wrap woosponder-active-connections-tab">'; // Added class for specific tab styling

    // Title for the 'Active Connections' section
    echo "<h2>חיבורים פעילים</h2>";

    echo '<div class="woosponder-search">';
    echo '<input type="text" id="search-connections" placeholder="חיפוש חיבור..." />';
    echo "</div>"; // End of search container

    // Placeholder for connections list, to be populated by JavaScript
    echo '<div id="connections-list" class="woosponder-connections-list">טוען חיבורים...</div>'; // Start of connections list with Test Content

    echo "</div>"; // End of main container with specific class
}