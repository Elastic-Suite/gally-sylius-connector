# Gally Plugin for Sylius

## Usage

- Install the `gally/sylius-plugin` bundle.
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

