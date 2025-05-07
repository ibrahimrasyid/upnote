<?php
require_once __DIR__ . '/../config/database.php';

class Transaction {
    private $transactionId;
    private $amount;
    private $type;
    private $date;
    private $description;
    private $categoryId;
    private $userId;
    
    // Constructor
    public function __construct($transactionId = null) {
        if ($transactionId) {
            $this->transactionId = $transactionId;
            $this->loadTransaction();
        }
    }
    
    // Load transaction data from database
    private function loadTransaction() {
        $sql = "SELECT * FROM TRANSACTION WHERE Transaction_ID = $this->transactionId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->amount = $result['Amount'];
            $this->type = $result['Type'];
            $this->date = $result['Date'];
            $this->description = $result['Description'];
            $this->categoryId = $result['Category_ID'];
            $this->userId = $result['User_ID'];
        }
    }
    
    // Getters
    public function getId() {
        return $this->transactionId;
    }
    
    public function getAmount() {
        return $this->amount;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function getDate() {
        return $this->date;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getCategoryId() {
        return $this->categoryId;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    // Get transactions by user ID
    public static function getByUserId($userId, $limit = null, $offset = 0) {
        $limitClause = $limit ? "LIMIT $offset, $limit" : "";
        
        $sql = "SELECT t.*, c.Category_Name 
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                ORDER BY t.Date DESC
                $limitClause";
                
        return fetch_all($sql);
    }
    
    // Get transaction by ID
    public static function getById($transactionId) {
        $sql = "SELECT t.*, c.Category_Name 
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.Transaction_ID = $transactionId";
                
        return fetch_row($sql);
    }
    
    // Get transactions by date range
    public static function getByDateRange($userId, $startDate, $endDate) {
        $sql = "SELECT t.*, c.Category_Name 
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                  AND t.Date BETWEEN '$startDate' AND '$endDate'
                ORDER BY t.Date DESC";
                
        return fetch_all($sql);
    }
    
    // Get transactions by month (YYYY-MM)
    public static function getByMonth($userId, $month) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return self::getByDateRange($userId, $startDate, $endDate);
    }
    
    // Get transactions by type (income/expense)
    public static function getByType($userId, $type) {
        $sql = "SELECT t.*, c.Category_Name 
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                  AND t.Type = '$type'
                ORDER BY t.Date DESC";
                
        return fetch_all($sql);
    }
    
    // Get transactions by category
    public static function getByCategory($userId, $categoryId) {
        $sql = "SELECT t.*, c.Category_Name 
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                  AND t.Category_ID = $categoryId
                ORDER BY t.Date DESC";
                
        return fetch_all($sql);
    }
    
    // Get total amount by type and date range
    public static function getTotalByTypeAndDateRange($userId, $type, $startDate, $endDate) {
        $sql = "SELECT SUM(Amount) as total
                FROM TRANSACTION
                WHERE User_ID = $userId
                  AND Type = '$type'
                  AND Date BETWEEN '$startDate' AND '$endDate'";
                
        $result = fetch_row($sql);
        return $result ? $result['total'] : 0;
    }
    
    // Get total amount by month and type
    public static function getTotalByMonthAndType($userId, $month, $type) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return self::getTotalByTypeAndDateRange($userId, $type, $startDate, $endDate);
    }
    
    // Get total income and expense by month
    public static function getSummaryByMonth($userId, $month) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $income = self::getTotalByTypeAndDateRange($userId, 'income', $startDate, $endDate);
        $expense = self::getTotalByTypeAndDateRange($userId, 'expense', $startDate, $endDate);
        
        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense
        ];
    }
    
    // Get category summary by month
    public static function getCategorySummaryByMonth($userId, $month, $type) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT c.Category_ID, c.Category_Name, SUM(t.Amount) as total
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                  AND t.Type = '$type'
                  AND t.Date BETWEEN '$startDate' AND '$endDate'
                GROUP BY c.Category_ID
                ORDER BY total DESC";
                
        return fetch_all($sql);
    }
    
    // Create a new transaction
    public static function create($amount, $type, $date, $description, $categoryId, $userId) {
        $sql = "INSERT INTO TRANSACTION (Amount, Type, Date, Description, Category_ID, User_ID) 
                VALUES ($amount, '$type', '$date', '$description', $categoryId, $userId)";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Transaction added successfully.',
                'transaction_id' => last_insert_id()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add transaction. Please try again.'
            ];
        }
    }
    
    // Update a transaction
    public function update($amount, $type, $date, $description, $categoryId) {
        $sql = "UPDATE TRANSACTION 
                SET Amount = $amount, 
                    Type = '$type', 
                    Date = '$date', 
                    Description = '$description', 
                    Category_ID = $categoryId 
                WHERE Transaction_ID = $this->transactionId";
                
        if (execute_query($sql)) {
            // Update object properties
            $this->amount = $amount;
            $this->type = $type;
            $this->date = $date;
            $this->description = $description;
            $this->categoryId = $categoryId;
            
            return [
                'success' => true,
                'message' => 'Transaction updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update transaction. Please try again.'
            ];
        }
    }
    
    // Delete a transaction
    public function delete() {
        $sql = "DELETE FROM TRANSACTION WHERE Transaction_ID = $this->transactionId";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Transaction deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete transaction. Please try again.'
            ];
        }
    }
}