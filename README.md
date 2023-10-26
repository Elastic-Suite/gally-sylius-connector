# Gally Plugin for Sylius

## Usage

- Install the `gally/sylius-plugin` bundle 
  - Run `composer require gally/sylius-plugin`
  - Add the bundle in `config/bundles.php`. You must put it after `SyliusGridBundle`
    ```php
    [...]
    Gally\SyliusPlugin\GallySyliusPlugin::class => ['all' => true],
    ```
  - Import routes by creating a file `config/routes/gally_admin.yaml`
    ```yaml
    gally_admin:
        resource: "@GallySyliusPlugin/Resources/config/admin_routing.yml"
        prefix: /admin
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
    
    /**
     * @ORM\Entity
     * @ORM\Table(name="sylius_channel")
     */
     #[ORM\Entity]
     #[ORM\Table(name: 'sylius_channel')]
     class Channel extends BaseChannel implements GallyChannelInterface
     {
        use GallyChannelTrait;
     }
     ```
    - Copy the templates from `vendor/gally/sylius-plugin/src/Resources/views/SyliusShopBundle/` to `templates/bundles/SyliusShopBundle/`.
    - Run `php bin/console doctrine:migrations:migrate` to update the database schema
    - Open Sylius Admin, head to Configuration > Gally and configure the Gally endpoint (URL, credentials)
- Run this commands from your Sylius instance. This commands must be runned only once to synchronize the structure.
    ```shell
        bin/console gally:structure-sync   # Sync catalog et source field data with gally
    ```
- Run a full index from Sylius to Gally. This command can be run only once. Afterwards, the modified products are automatically synchronized.
    ```shell
        bin/console gally:index            # Index category and product entity to gally
    ```
- At this step, you should be able to see your product and source field in the Gally backend.
- They should also appear in your Sylius frontend when searching or browsing categories.
- And you're done !

