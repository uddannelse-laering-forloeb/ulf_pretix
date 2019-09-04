# ULF Pretix

## Installation instructions
- Requires Secure permissions 2.x-dev

Enable the module and configure endpoint and default settings at "Indstillinger > Ding > Pretix Settings".
You will need access to and API keys for a running Pretix installation. Either self-hosted or at pretix.eu.


## ULF project relation
- Optional module

## Description
  - Adds fields to user profiles and course for connecting to Pretix through API
  - Adds new field collection for mapping an event in Pretix
  - Holds code for connecting to Pretix through Pretix API

The module will add a 'pretix' section on all courses.

## Coding standards

```sh
composer install
composer check-coding-standards
```
