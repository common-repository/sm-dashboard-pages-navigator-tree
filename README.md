SM Dashboard Pages Navigator Tree
=================================

[![CircleCI](https://circleci.com/gh/WordPress-Phoenix/sm-dashboard-pages-navigator-tree/tree/master.svg?style=svg)](https://circleci.com/gh/WordPress-Phoenix/sm-dashboard-pages-navigator-tree/tree/master)

Navigational Dashboard Widget for easily finding your pages in a heirarchy page tree view on your WordPress admin dashboard

## UPDATING `/lib` files

Lib files come from composer, but you need to ensure you run the command without the composer autoloader:
```
composer update --no-dev --no-autoloader
```