-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 07, 2025 at 12:38 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finance_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `Budget_ID` int NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Month` varchar(7) NOT NULL,
  `Category_ID` int NOT NULL,
  `User_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget`
--

INSERT INTO `budget` (`Budget_ID`, `Amount`, `Month`, `Category_ID`, `User_ID`) VALUES
(2, '500000.00', '2025-04', 11, 1),
(3, '200000.00', '2025-04', 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `Category_ID` int NOT NULL,
  `Category_Name` varchar(50) NOT NULL,
  `User_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`Category_ID`, `Category_Name`, `User_ID`) VALUES
(1, 'Salary', 1),
(2, 'Bonus', 1),
(3, 'Investment', 1),
(4, 'Other Income', 1),
(5, 'Food', 1),
(6, 'Housing', 1),
(7, 'Transportation', 1),
(8, 'Entertainment', 1),
(9, 'Health', 1),
(10, 'Education', 1),
(11, 'Shopping', 1),
(12, 'Utilities', 1),
(13, 'Other Expense', 1),
(14, 'Traveling', 1);

-- --------------------------------------------------------

--
-- Table structure for table `goal`
--

CREATE TABLE `goal` (
  `Goal_ID` int NOT NULL,
  `Target_Amount` decimal(10,2) NOT NULL,
  `Target_Date` date NOT NULL,
  `Progress` decimal(10,2) DEFAULT '0.00',
  `Status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `User_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `goal`
--

INSERT INTO `goal` (`Goal_ID`, `Target_Amount`, `Target_Date`, `Progress`, `Status`, `User_ID`) VALUES
(2, '2000000.00', '2025-05-28', '100000.00', 'in_progress', 1);

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `Report_ID` int NOT NULL,
  `Period_Start` date NOT NULL,
  `Period_End` date NOT NULL,
  `Total_Income` decimal(10,2) NOT NULL,
  `Total_Expense` decimal(10,2) NOT NULL,
  `User_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`Report_ID`, `Period_Start`, `Period_End`, `Total_Income`, `Total_Expense`, `User_ID`) VALUES
(1, '2025-04-01', '2025-04-30', '2500000.00', '649000.00', 1),
(2, '2025-04-01', '2025-04-29', '2500000.00', '539000.00', 1),
(3, '2025-04-01', '2025-04-27', '2500000.00', '539000.00', 1),
(4, '2025-04-01', '2025-04-26', '2500000.00', '539000.00', 1),
(5, '2025-05-01', '2025-05-31', '0.00', '0.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `Transaction_ID` int NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Type` enum('income','expense') NOT NULL,
  `Date` date NOT NULL,
  `Description` text,
  `Category_ID` int NOT NULL,
  `User_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`Transaction_ID`, `Amount`, `Type`, `Date`, `Description`, `Category_ID`, `User_ID`) VALUES
(1, '2500000.00', 'income', '2025-04-26', '', 1, 1),
(2, '499000.00', 'expense', '2025-04-26', '', 11, 1),
(4, '50000.00', 'expense', '2025-04-27', '', 5, 1),
(5, '100000.00', 'expense', '2025-04-30', '', 8, 1),
(7, '2500000.00', 'income', '2025-05-07', '', 1, 1),
(8, '350000.00', 'expense', '2025-05-10', '', 11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Name`, `Email`, `Password`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$2T9UUTDd9eGC9IFAbqs2NeKq9odGju.GpWQ45nSUhrU8an52bq1XS');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `user` FOR EACH ROW BEGIN
    -- Insert default income categories
    INSERT INTO CATEGORY (Category_Name, User_ID) VALUES 
        ('Salary', NEW.User_ID),
        ('Bonus', NEW.User_ID),
        ('Investment', NEW.User_ID),
        ('Other Income', NEW.User_ID);
    
    -- Insert default expense categories
    INSERT INTO CATEGORY (Category_Name, User_ID) VALUES 
        ('Food', NEW.User_ID),
        ('Housing', NEW.User_ID),
        ('Transportation', NEW.User_ID),
        ('Entertainment', NEW.User_ID),
        ('Health', NEW.User_ID),
        ('Education', NEW.User_ID),
        ('Shopping', NEW.User_ID),
        ('Utilities', NEW.User_ID),
        ('Other Expense', NEW.User_ID);
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`Budget_ID`),
  ADD UNIQUE KEY `unique_budget` (`Month`,`Category_ID`,`User_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`Category_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `goal`
--
ALTER TABLE `goal`
  ADD PRIMARY KEY (`Goal_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`Transaction_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `Budget_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `Category_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `goal`
--
ALTER TABLE `goal`
  MODIFY `Goal_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `Report_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `Transaction_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget`
--
ALTER TABLE `budget`
  ADD CONSTRAINT `budget_ibfk_1` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `goal`
--
ALTER TABLE `goal`
  ADD CONSTRAINT `goal_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
