<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Transaction.php';

class Report {
    private $reportId;
    private $periodStart;
    private $periodEnd;
    private $totalIncome;
    private $totalExpense;
    private $userId;
    
    // Constructor
    public function __construct($reportId = null) {
        if ($reportId) {
            $this->reportId = $reportId;
            $this->loadReport();
        }
    }
    
    // Load report data from database
    private function loadReport() {
        $sql = "SELECT * FROM REPORT WHERE Report_ID = $this->reportId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->periodStart = $result['Period_Start'];
            $this->periodEnd = $result['Period_End'];
            $this->totalIncome = $result['Total_Income'];
            $this->totalExpense = $result['Total_Expense'];
            $this->userId = $result['User_ID'];
        }
    }
    
    // Getters
    public function getId() {
        return $this->reportId;
    }
    
    public function getPeriodStart() {
        return $this->periodStart;
    }
    
    public function getPeriodEnd() {
        return $this->periodEnd;
    }
    
    public function getTotalIncome() {
        return $this->totalIncome;
    }
    
    public function getTotalExpense() {
        return $this->totalExpense;
    }
    
    public function getBalance() {
        return $this->totalIncome - $this->totalExpense;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    // Get all reports by user ID
    public static function getByUserId($userId) {
        $sql = "SELECT * FROM REPORT WHERE User_ID = $userId ORDER BY Period_End DESC";
        return fetch_all($sql);
    }
    
    // Get report by ID
    public static function getById($reportId) {
        $sql = "SELECT * FROM REPORT WHERE Report_ID = $reportId";
        return fetch_row($sql);
    }
    
    // Generate a new report for a specific period
    public static function generate($userId, $periodStart, $periodEnd) {
        // Calculate totals
        $totalIncome = Transaction::getTotalByTypeAndDateRange($userId, 'income', $periodStart, $periodEnd);
        $totalExpense = Transaction::getTotalByTypeAndDateRange($userId, 'expense', $periodStart, $periodEnd);
        
        // PERBAIKAN: Pastikan nilai tidak NULL dengan mengubahnya menjadi 0
        $totalIncome = $totalIncome ?: 0; // Sama dengan: is_null($totalIncome) || $totalIncome === false ? 0 : $totalIncome
        $totalExpense = $totalExpense ?: 0;
        
        // Check if a report already exists for this period
        $sql = "SELECT * FROM REPORT 
                WHERE User_ID = $userId 
                AND Period_Start = '$periodStart' 
                AND Period_End = '$periodEnd'";
        $existingReport = fetch_row($sql);
        
        if ($existingReport) {
            // Update existing report
            $sql = "UPDATE REPORT 
                    SET Total_Income = $totalIncome, Total_Expense = $totalExpense 
                    WHERE Report_ID = " . $existingReport['Report_ID'];
                    
            if (execute_query($sql)) {
                return [
                    'success' => true,
                    'message' => 'Report updated successfully.',
                    'report_id' => $existingReport['Report_ID'],
                    'is_new' => false
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update report. Please try again.'
                ];
            }
        } else {
            // Create new report
            $sql = "INSERT INTO REPORT (Period_Start, Period_End, Total_Income, Total_Expense, User_ID) 
                    VALUES ('$periodStart', '$periodEnd', $totalIncome, $totalExpense, $userId)";
                    
            if (execute_query($sql)) {
                return [
                    'success' => true,
                    'message' => 'Report generated successfully.',
                    'report_id' => last_insert_id(),
                    'is_new' => true
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to generate report. Please try again.'
                ];
            }
        }
    }
    
    // Get income vs expense data for chart (monthly for the last 6 months)
    public static function getMonthlyComparisonData($userId) {
        $data = [];
        
        // Get data for the last 6 months
        for ($i = 0; $i < 6; $i++) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime("-$i months"));
            
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $income = Transaction::getTotalByTypeAndDateRange($userId, 'income', $startDate, $endDate);
            $expense = Transaction::getTotalByTypeAndDateRange($userId, 'expense', $startDate, $endDate);
            
            // PERBAIKAN: Pastikan nilai tidak NULL
            $income = $income ?: 0;
            $expense = $expense ?: 0;
            
            $data[] = [
                'month' => $monthName,
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense
            ];
        }
        
        // Reverse array to get chronological order
        return array_reverse($data);
    }
    
    // Get category breakdown for a specific period
    public static function getCategoryBreakdown($userId, $periodStart, $periodEnd, $type = 'expense') {
        $sql = "SELECT c.Category_Name, COALESCE(SUM(t.Amount), 0) as total
                FROM TRANSACTION t
                JOIN CATEGORY c ON t.Category_ID = c.Category_ID
                WHERE t.User_ID = $userId
                  AND t.Type = '$type'
                  AND t.Date BETWEEN '$periodStart' AND '$periodEnd'
                GROUP BY c.Category_ID
                ORDER BY total DESC";
                
        return fetch_all($sql);
    }
    
    // Delete a report
    public function delete() {
        $sql = "DELETE FROM REPORT WHERE Report_ID = $this->reportId";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Report deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete report. Please try again.'
            ];
        }
    }
}