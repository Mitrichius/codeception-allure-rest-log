# Codeception Allure REST Log Extension
Add REST request log to Allure Framework for failed tests

### Installation
Run 
```
php composer.phar require "mitrichius/codeception-allure-rest-log"
```

or add require string to composer.json

```json
"require-dev": {
	"mitrichius/codeception-allure-rest-log": "dev-master",
}
```
### Configuration
Enable extension in codeception global config codeception.yml
```yaml
extensions:
  enabled:
    ...
    - Codeception\Extension\AllureRestLogExtension
    ...
```

### Issues
Some requests may be skipped if one codeception step contains two or more requests (e.g. method from helper)