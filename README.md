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


 ```bash
 composer require devtools/module-cmsapi
 ```

If you’re installing directly from a GitHub repo:

```bash
composer config repositories.devtools-cmsapi vcs https://github.com/23Yashu/module-cmsapi.git
composer require devtools/module-cmsapi
```

2. **Enable the module**

```bash
bin/magento module:enable DevTools_CmsApi
bin/magento setup:upgrade
bin/magento cache:flush
```


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
```json
{
  "body": {
    "identifier": "home",
    "type": "page",
    "format": "json",
    "content_type": ["products", "slider"]
  }
}
```

**Example Response**
```json
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
```

<img width="663" height="433" alt="Screenshot 2025-09-19 at 11 39 38 AM" src="https://github.com/user-attachments/assets/3850124f-aaa3-4e43-9048-df6af2cc5391" />


### Extending
This module was written with extensibility in mind.
You can extend or modify the response by creating your own Plugin or Observer in your project.

### Notes
Developed & tested with Mark Shust's Docker Magento setup.

This is part of my personal exploration of Magento APIs.

Contributions, forks, and extensions are welcome! 🎉

### License
This project is open-sourced software licensed under the MIT license.


## Author's Note -
If you are using Mark Shust's Docker setup, the above steps might not install the module in the Magento. In case you get stuck, please try to follow these steps -
1. From project root:

```bash
docker exec -e COMPOSER_MEMORY_LIMIT=-1 magento-phpfpm-1 sh -lc 'composer install --no-interaction --prefer-dist'
```

2. If adding the module for the first time:

```bash
docker exec -e COMPOSER_MEMORY_LIMIT=-1 magento-phpfpm-1 sh -lc 'composer require devtools/module-cmsapi:dev-main --no-interaction --prefer-dist'
```

3. Lastly:

```bash
docker exec magento-phpfpm-1 sh -lc 'bin/magento setup:upgrade'
```


