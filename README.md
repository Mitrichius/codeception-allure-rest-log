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
Extension uses fork of allure-codeception to work with codeception 2.2:
https://github.com/Mitrichius/allure-codeception