# Magento2_SomethingDigital_UpgradeHelper

## Usage

### Generating the diff

- Download a ZIP of the old version
- Download a ZIP of the new version
- Use `diff -r` to generate the diff

Example:

```
diff -r magento-2-2-6-ee magento-2-2-7-ee > magento-2-2-6-ee--2-2-7-ee.diff
```

### How To Run

```
$ bin/magento sd:dev:upgrade-helper magento-2-2-6-ee--2-2-7-ee.diff
```

#### Example Output

```
$ bin/magento sd:dev:upgrade-helper magento-2-2-6-ee--2-2-7-ee.diff
There is a preference for: vendor/magento/module-catalog/Block/Product/ImageBuilder.php
--- preference: Company\Module\Block\Product\ImageBuilder
```

In this case `vendor/magento/module-catalog/Block/Product/ImageBuilder.php` will be changed by the patch, however there is a preference (`Company\Module\Block\Product\ImageBuilder`) for that file.