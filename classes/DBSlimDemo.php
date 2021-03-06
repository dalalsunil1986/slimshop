<?php

/**
 * Class DBSlimDemo implements a demo page for accessing MariaDB with PDO
 * and uses Slim Controllers and Middleware to implement a MVC pattern.
 *
 * Due to the usage of PDO Prepared Statements no further steps are necessary to avoid SQL Injection in this use case.
 * XSS is prevented by the TWIG template engine, that escapes variables sent to a template automatically.
 *
 * This page lists the content of onlineshop.product_category and adds additional categories.
 *
 * Class DBSlimDemo is final, because it makes no sense to derive a class from it.
 *
 * @author  Martin Harrer <martin.harrer@fh-hagenberg.at>
 * @package slimshop
 * @version 2019
 */
final class DBSlimDemo extends Controller
{
    /**
     * Constant for a HTML attribute in <input name='ptype' id='ptype' ... >, <label for='ptype' ... >
     * --> $_POST[self::PTYPE]
     */
    const PTYPE = 'ptype';

    /*
     * @return the initial view after a GET request for /dbajaxdemo
     */
    public function index ($request, $response) {
        $this->logger->info("Slim-Skeleton '/dbslimdemo' route");
        $args['pageArray'] = $this->fillpageArray();
        return $this->view->render($response, 'dbslimdemoMain.html.twig', $args);
    }

    /**
     * Validates the user input
     *
     * The product category ptype is tested if it is empty.
     * Additionally it is validated with a regex given by Utilities::isSingleWord().
     * Due to "use Utilities" at the begin of this class, $this->isSingleWord() is also possible.
     * The trait is part of the current class then.
     * Error messages are written to the array $errorMessages[].
     *
     * Abstract methods of the class AbstractNormform have to be implemented in the derived class.
     *
     * @return bool true, if $errorMessages is empty, else false
     */
    private function isValid($request): bool
    {
        if ($this->isEmptyPostField(self::PTYPE, $request)) {
            $this->errorMessages[self::PTYPE] = "Please enter a Product Category.";
        }
        if (!Utilities::isSingleWord($request->getParam(self::PTYPE))) {
            $this->errorMessages[self::PTYPE] = "Please enter a Product Category as a Single Word.";
        }
        return (count($this->errorMessages) === 0);
    }

    /**
     * Process the user input, sent with a POST request
     *
     * Shop::addPType() stores a new category in onlineshop.product_category.
     * If this works $this->statusMsg is set and displayed in the template.
     * All categories are read from onlineshop.product_category and displayed in the template.
     *
     * Abstract methods of the class AbstractNormform have to be implemented in the derived class.
     *
     * @throws DatabaseException is thrown by all methods of $this->dbAccess and not treated here.
     *         The exception is treated in the try-catch block of the php script, that initializes this class.
     */
    public function business($request, $response)
    {
        if ($this->isValid($request)) {
            $this->addPType();
            $this->statusMessage = "Product Category " . $request->getParam(self::PTYPE) . " added";
            $args = ["statusMessage" => $this->statusMessage,
                "pageArray" => $this->fillPageArray(),
            ];
        } else {
            $args = $this->returnInputParams($request);
            $args['errorMessages'] = $this->errorMessages;
            $args['pageArray'] = $this->fillPageArray();
        }
        return $this->view->render($response, 'dbslimdemoMain.html.twig', $args);
    }

    /**
     * Returns an array to display all entries of onlineshop.product_category on the current page.
     *
     * @return array $result Result set of database query.
     * @throws DatabaseException is thrown by all methods of $this->dbAccess and not treated here.
     *         The exception is treated in the try-catch block of the php script, that initializes this class.
     */
    private function fillPageArray(): array
    {
        $query = <<<SQL
                 SELECT idproduct_category, product_category_name
                 FROM product_category
SQL;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }


    /**
     * Writes validated user input to the table onlineshop.product_category.
     *
     * @throws DatabaseException is thrown by all methods of $this->dbAccess and not treated here.
     *         The exception is treated in the try-catch block of the php script, that initializes this class.
     */
    private function addPType(): int
    {
        $query = <<<SQL
                 INSERT INTO product_category 
                 SET product_category_name = :ptype
SQL;
        $stmt = $this->db->prepare($query);
        $params = array(':ptype' => $_POST[self::PTYPE]);
        $stmt->execute($params);
        return $this->db->lastInsertID();
    }
}
