SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `PListType` (`ID`, `Description`, `InStats`) VALUES
(1, 'Обязательный листок', 1),
(2, 'Дополнительный листок', 1);

INSERT INTO `PProblemType` (`ID`, `Sign`, `Description`) VALUES
(0, '', 'Обычная задача'),
(1, '*', 'Сложная задача'),
(2, '**', 'Очень сложная задача'),
(3, '°', 'Важная задача');

INSERT INTO `PRole` (`Name`, `ManageMarks`, `ManageLists`, `ManageClasses`, `ManageUsers`) VALUES
('Admin', 1, 1, 1, 1),
('Teacher', 1, 0, 0, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
