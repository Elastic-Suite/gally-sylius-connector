# Gally Plugin for Sylius

## Requirements

- Gally version: 2.1.x
- Sylius version: 2.0.x

## Usage

- Install the `gally/sylius-plugin` bundle 
  - Run `composer require gally/sylius-plugin`
  - Add the bundle in `config/bundles.php`. You must put it after `SyliusGridBundle`
    ```php
    [...]
    Gally\SyliusPlugin\GallySyliusPlugin::class => ['all' => true],
    ```
  - Import the Gally Sylius bundle configuration by adding the following lines to the imports section of `config/packages/_sylius.yaml`
    ```yaml
    - { resource: "@GallySyliusPlugin/Resources/config/config.yml" }
    ```
  - Import admin routes by creating a file `config/routes/gally_admin.yaml`
    ```yaml
    gally_admin:
        resource: "@GallySyliusPlugin/Resources/config/admin_routing.yml"
        prefix: /admin
    ```
  - Import shop routes by creating a file `config/routes/gally_shop.yaml`
    ```yaml
    gally_shop:
        resource: "@GallySyliusPlugin/Resources/config/shop_routing.yml"
        prefix: /{_locale}
    ```
   - Implement the `Gally\SyliusPlugin\Model\GallyChannelInterface` and `Gally\SyliusPlugin\Model\GallyChannelTrait` in your Channel Entity `src/App/Entity/Channel/Channel.php`.
    ```php
    <?php
    
    declare(strict_types=1);
    
    namespace App\Entity\Channel;
    
    use Doctrine\ORM\Mapping as ORM;
    use Gally\SyliusPlugin\Model\GallyChannelInterface;
    use Gally\SyliusPlugin\Model\GallyChannelTrait;
    use Sylius\Component\Core\Model\Channel as BaseChannel;
    
     #[ORM\Entity]
     #[ORM\Table(name: 'sylius_channel')]
     class Channel extends BaseChannel implements GallyChannelInterface
     {
        use GallyChannelTrait;
     }
     ```
    - Copy the bundle assets (Javascript & CSS files):
       - Run `php bin/console assets:install`
       - Run `php bin/console sylius:install:assets`
       - Run `php bin/console sylius:theme:assets:install`

    - **Alternative: install assets via Webpack Encore (recommended for Sylius 2.x)**
      - Add the plugin as a npm dependency in your app's `package.json`:
        ```json
        {
          "dependencies": {
            "@gally/sylius-plugin": "file:vendor/gally/sylius-plugin"
          }
        }
        ```
        > **Development only:** if you are working on the plugin sources directly (i.e. pointing to
        > `file:packages/GallyPlugin` instead of `file:vendor/gally/sylius-plugin`), you must also add
        > a `postinstall` script to build the `@gally/sdk` dist files, which are not pre-compiled on the GitHub branch:
        > ```json
        > {
        >   "scripts": {
        >     "postinstall": "cd node_modules/@gally/sdk && yarn install"
        >   }
        > }
        > ```
      - Add the Gally shop entry point and the SDK IIFE copy in your app's `webpack.config.js`:
        ```js
        Encore
            // ... your existing shop config
            .addEntry('gally-shop-entry', './vendor/gally/sylius-plugin/src/Resources/assets/shop/entrypoint.js')
            .copyFiles({
                from: './node_modules/@gally/sdk/dist/browser/iife',
                to: 'gally/[name].[ext]',
                pattern: /gally-sdk\.global\.js/,
            })
        ;
        ```
        > The `copyFiles()` call exposes `gally-sdk.global.js` as a standalone IIFE script
        > (available as `window.GallySDK`) for use in Twig templates via `{{ asset('build/app/shop/gally/gally-sdk.global.js') }}`.
      - Install JS dependencies and build assets:
        ```shell
        yarn install
        yarn build
        ```
    - Run `php bin/console doctrine:migrations:migrate` to update the database schema
    - Open Sylius Admin, head to Configuration > Gally and configure the Gally endpoint (URL, credentials), after that enable Gally on your channel (Configuration > Channel > Edit)
- Run this commands from your Sylius instance. This commands must be runned only once to synchronize the structure.
    ```shell
        bin/console gally:structure:sync   # Sync catalog et source field data with gally
    ```
- Run a full index from Sylius to Gally. This command can be run only once. Afterwards, the modified products are automatically synchronized.
    ```shell
        bin/console gally:index            # Index category and product entity to gally
    ```
- At this step, you should be able to see your product and source field in the Gally backend.
- They should also appear in your Sylius frontend when searching or browsing categories.
- And you're done !
- You can also run the command to clean data that are not present in sylius anymore:
    ```shell
        bin/console gally:structure:clean 
    ```

## noUiSlider 

This bundle includes the [noUiSlider](https://github.com/leongersen/noUiSlider) distribution files. 
noUiSlider is "a lightweight, ARIA-accessible JavaScript range slider with multi-touch and keyboard support" which is used in this project for the price slider implementation.

