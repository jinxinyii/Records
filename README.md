# Records Management System

## Overview
The Records Management System is designed to help users log their working hours efficiently. It allows users to input their time in and time out, and it calculates the total hours worked.

## Features
- **Time In/Out Forms**: Users can easily log their time in and time out.
- **Time Log Table**: A table that displays all logged times with the date, time in, time out, and total time.
- **Total Overall Hours**: Displays the total hours worked in a user-friendly format.

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/jinxinyii/Records.git
   ```
2. Navigate to the project directory:
   ```bash
   cd records-management
   ```
3. Set up the database:
   - Create a database named `user_db`.
   - Import the SQL schema if provided.

4. Update the `config.php` file with your database credentials.

## Usage
- Open `index.php` in your web browser to log in first.
- After logging in, navigate to `dashboard.php` to use the forms to log your time.
- View your logged times in the table below the forms.

## Contributing
If you would like to contribute to this project, please fork the repository and submit a pull request.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments
- Thanks to the contributors and the open-source community for their support.
- Special thanks to the developers of PHP and MySQL for providing the tools to build this application.