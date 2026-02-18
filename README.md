# Broken Shop (Overlay)

This repository is an *overlay* meant to be applied on top of a fresh Laravel install.

## Project Overview
This is a broken implementation of an e-commerce app which allows users to perform the followings : 
- Browse Products
- Add to cart
- checkout

## Setup
1) Create a fresh project:
   composer create-project laravel/laravel broken-shop
   cd broken-shop

2) Unzip this overlay into the project root (overwrite files):

3) Run migrations:
   php artisan queue:table
   php artisan migrate
   php artisan db:seed

4) Run queue worker (database driver):
   php artisan queue:work

# The Challenge
This task is designed to test your skills in the following areas : 
- Judgment
- Prioritization
- Depth in critical areas



## Deliverables
The Core Cycle ( View Products, Add to Cart, Checkout should work correctly without errors )

You do not need to fix everything. Focus on correctness, payment safety, Security and performance.
Leave notes where you would improve further.

