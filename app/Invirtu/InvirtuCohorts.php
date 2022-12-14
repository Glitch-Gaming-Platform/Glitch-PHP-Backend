<?php


namespace App\Invirtu;

use Http\Client\Exception;
use stdClass;

class InvirtuCohorts extends InvirtuResource
{

    /**
     * Gets Collections
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param array $options
     * @return stdClass
     */
    public function list(array $options = [])
    {
        return $this->client->get('collections', $options);
    }

    /**
     * Creates new Collection
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param array $options
     * @return stdClass
     */
    public function create(array $options = [])
    {
        return $this->client->post('collections', $options);
    }

    /**
     * Gets a single Collection by ID
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param string $id
     * @return stdClass
     */
    public function get($id)
    {
        $path = $this->collectionPath($id);

        return $this->client->get($path);
    }

    /**
     * Update a single Collection by ID
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param string $id Collection ID
     * @param array $options
     * @return stdClass
     */
    public function update($id, array $options)
    {
        $path = $this->collectionPath($id);

        return $this->client->put($path, $options);
    }

    /**
     * Delete a Collection by ID
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param string $id Collection ID
     * @return stdClass
     */
    public function delete($id)
    {
        $path = $this->collectionPath($id);

        return $this->client->delete($path);
    }

    /**
     * Get Products in Collection
     *
     * @see    https://developers.thinkific.com/api/api-documentation/
     * @param string $id
     * @param array $options
     * @return stdClass
     */
    public function getProducts($id, array $options = [])
    {
        $path = $this->collectionPath($id);

        return $this->client->get($path.'/products', $options);
    }

    /**
     * @param string $id
     * @return string
     */
    public function collectionPath(string $id)
    {
        return 'collections/' . $id;
    }
}
