## About

PHP package for Instagram API. With this PHP package, you can view user media using the Instagram API.

## Requirements

- PHP 8.0 or higher
- Guzzle
- Registered Instagram App



### Initialize the class

```php
<?php
    require_once 'Instagram.php';
    use niyazialpay\Instagram;
    
    $instagram = new Instagram(array(
      'apiKey'      => 'YOUR_APP_KEY',
      'apiSecret'   => 'YOUR_APP_SECRET',
      'apiCallback' => 'YOUR_APP_CALLBACK'
    ));
    
    echo "<a href='{$instagram->getLoginUrl()}'>Login with Instagram</a>";
?>
```


### Authenticate user (OAuth2)

```php
<?php
    $code = $_GET['code'];
    $data = $instagram->getOAuthToken($code);
    
    echo 'Your username is: ' . $data->user->username;
?>
```


### Long Lived Token

```php
<?php
    $code = $_GET['code'];
    $long_lived_token = $instagram::getLongLivedToken($_GET['code']);
?>
```


### Refresh Long Lived Token

```php
<?php
    $long_lived_token = $instagram::RefreshToken('Old long lived token will be added here before expiration');
?>
```
