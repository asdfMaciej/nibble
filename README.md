# Framework overview
Define the required constants (they're listed in a section below) and **require "/framework/include.php" in index.php** to get started.

In index.php, you should create a **new Router()** instance. Define the routes using regex and refering to page names.

Pages are searched for in the **/pages/** folder. Pages should use the **response builders** provided - JSONBuilder & **WebBuilder**. They encompass database connection, rendering, storing metadata and retrieving data.

**Templating system** is used for rendering the webpages. Templates are stored in the **/templates/** folder. They utilize PHP built-in short tags, enable nesting themselves, and allow for escaping $variables as **{{variable}}** for safe display. You can **pass data** to them.

**ORM** is provided for **database and session models**. Creating a basic DB model requires you to specify table name, declare fields [as public variables] and specify the primary key. You can also utilize **aliases, computed variables, and retrieving & saving methods**. For more advanced select queries, you can join various models together using a query builder. When using session models, **data is autosaved** and the structure is inited to specified default values.


# Required constants
**DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE** - self-explanatory, for connection with a MySQL database

**PATH_PREFIX** - the prefix shown in browser - for example.com/your/app/<the rest> it should be /your/app
  
**ROOT_PATH** - directory of the application on the server, for ex. if your files are in a directory /myapp/ [relative to webserver root], then use $_SERVER["DOCUMENT_ROOT"] . "/myapp"

**SESSION_NAME** - a name to use for storing application's session, so it doesn't collide with other applications on the same server

In this example, they're stored in **/env/config.php** and imported in **application/app.php**

