# DevTools_CmsApi

A Magento 2 module that provides a custom JSON API for CMS Pages and Blocks.  
You can request CMS content in **HTML** or structured **JSON**, with optional filtering by PageBuilder content types such as `slider`, `slide`, or `products`.

This module has been tested with [Mark Shust's Docker Magento setup](https://github.com/markshust/docker-magento).  

---

## Features

- Exposes a custom REST API endpoint: `/V1/cms/page`
- Supports both **pages** and **blocks**
- Returns CMS content as **HTML** or structured **JSON**
- Filters by specific PageBuilder content types (e.g. only return `slider` or `products`)
- Built-in caching with automatic invalidation on CMS page/block save/delete
- Easy to extend with Plugins or additional Observers
- MIT Licensed — free to modify and extend

---

## Installation

1. **Require via Composer**

   If this repo is public on GitHub:

   ```bash
   composer require devtools/module-cmsapi
If you’re installing directly from a GitHub repo:

composer config repositories.devtools-cmsapi vcs https://github.com/<your-username>/module-cmsapi.git
composer require devtools/module-cmsapi

2. **Enable the module**
bin/magento module:enable DevTools_CmsApi
bin/magento setup:upgrade
bin/magento cache:flush

### Usage
**REST Endpoint**

POST /rest/V1/cms/page

Request Body Parameters
| Field          | Type              | Description                                              |
| -------------- | ----------------- | -------------------------------------------------------- |
| `identifier`   | string (required) | The CMS page or block identifier (e.g. `home`)           |
| `type`         | string (required) | Either `page` or `block`                                 |
| `format`       | string            | `html` or `json` (default: `json`)                       |
| `content_type` | string or array   | Filter by PageBuilder content type(s), e.g. `["slider"]` |


**Example Request (Postman)**

{
  "body": {
    "identifier": "home",
    "type": "page",
    "format": "json",
    "content_type": ["products", "slider"]
  }
}

**Example Response**

{
  "identifier": "home",
  "type": "page",
  "format": "json",
  "content": [
    {
      "type": "div",
      "content-type": "slider",
      "appearance": "default",
      "children": [
        {
          "type": "div",
          "content-type": "slide",
          "slide-name": "Sample Slide",
          "children": [...]
        }
      ]
    }
  ]
}


### Extending
This module was written with extensibility in mind.
You can extend or modify the response by creating your own Plugin or Observer in your project.

### Notes
Developed & tested with Mark Shust's Docker Magento setup.

This is part of my personal exploration of Magento APIs.

Contributions, forks, and extensions are welcome! 🎉

### License
This project is open-sourced software licensed under the MIT license.
