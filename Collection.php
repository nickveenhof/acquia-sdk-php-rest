<?php

namespace Acquia\Rest;

use Guzzle\Http\Message\Request;

class Collection extends \ArrayObject
{
    /**
     * @var \Guzzle\Http\Message\Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $elementClass = '\Acquia\Rest\Element';

    /**
     * The array key containing the collection, null if it is not nested.
     *
     * Alternately set an array of keys that the may contain the collection.
     * This is useful when working with inconsistent APIs that store collections
     * of the same elements in different properties depending on the endpoint
     * that is consumed.
     *
     * @var string|array
     */
    protected $collectionProperty;

    /**
     * @param \Guzzle\Http\Message\Request $request
     */
    public function __construct(Request $request)
    {
        $this->response = $request->send();
        parent::__construct($this->response->json());
    }

    /**
     * Keys the array of objects by their identifier, constructs and returns and
     * array object.
     *
     * When the object is cast to a string, its unique identifier is returned.
     *
     * @return \ArrayObject
     *
     * @throws \OutOfBoundsException
     *
     * @see \Acquia\Rest\Element::__toString()
     */
    public function getIterator($idColumn = NULL)
    {
        $array = $this->getArrayCopy();

        // Is the collection nested in the array?
        if (isset($this->collectionProperty)) {

            // Locate the collection in the response.
            $collectionFound = false;
            foreach ((array) $this->collectionProperty as $property) {
                if (isset($array[$property])) {
                    $collectionFound = true;
                    break;
                }
            }

            if (!$collectionFound) {
                throw new \OutOfBoundsException('Collection not found in response');
            }

            $array = $array[$property];
        }

        // Build the collection.
        $collection = array();
        foreach ($array as $item) {
            $element = new $this->elementClass($item);
            if (isset($idColumn)) {
                $element->setIdColumn($idColumn);
            }
            $collection[(string) $element] = $element;
        }

        return new \ArrayObject($collection);
    }

    /**
     * Returns the raw response body, usually a string containing JSON.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->response->getBody(true);
    }
}
