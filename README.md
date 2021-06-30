# Deployment Notes

## How to install

1. Copy `.env.example` file to `.env` file `$ cp .env.example .env`

2. Modify your configurations (like db host, db username, db password and interest rate) to `.env` file.

3. Import Postman collection from `<root>/storage/postman/collection.json`

4. Run install `$ sh install.sh`

## How to run the app

`$ php artisan serve`
