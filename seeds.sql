INSERT INTO `data_plans` (`network`, `type`, `quantity`, `price`) VALUES
('mtn', 'sme-data', '1gb', 590.00),
('mtn', 'sme-data', '2gb', 1180.00),
('mtn', 'sme-data', '3gb', 1770.00),
('mtn', 'sme-data', '5gb', 2950.00),
('mtn', 'cg-data', '500mb', 345.00),
('mtn', 'cg-data', '1gb', 485.00);

INSERT INTO `services` (`name`, `slug`, `icon_class`, `description`, `is_available`) VALUES
('Buy Airtime', 'airtime', 'fa-mobile-alt', 'Purchase airtime for any network.', 1),
('Buy Data', 'data', 'fa-wifi', 'Purchase data bundles for any network.', 1),
('Pay Cable TV', 'cable', 'fa-tv', 'Pay your cable TV subscriptions.', 1),
('Pay Electricity', 'electricity', 'fa-bolt', 'Pay your electricity bills.', 1),
('Exam Pins', 'exam', 'fa-graduation-cap', 'Purchase exam pins.', 1),
('Bank Transfer', 'bank-transfer', 'fa-paper-plane', 'Transfer money to any bank account.', 1);

INSERT INTO `networks` (`name`, `code`) VALUES
('MTN', 'mtn'),
('Glo', 'glo'),
('Airtel', 'airtel'),
('9mobile', '9mobile');

INSERT INTO `cable_tv_packages` (`provider`, `package_name`, `api_code`, `price`) VALUES
('dstv', 'DStv Padi', 'dstv-padi', 2150.00),
('dstv', 'DStv Yanga', 'dstv-yanga', 2950.00),
('gotv', 'GOtv Smallie', 'gotv-smallie', 900.00),
('gotv', 'GOtv Jinja', 'gotv-jinja', 1900.00),
('startimes', 'StarTimes Nova', 'startimes-nova', 900.00);

INSERT INTO `electricity_discos` (`name`, `api_code`) VALUES
('Ikeja Electric', 'ikeja-electric'),
('Eko Electric', 'eko-electric'),
('Abuja Electric', 'abuja-electric'),
('Kano Electric', 'kano-electric'),
('Port Harcourt Electric', 'phed-electric');

INSERT INTO `banks` (`name`, `code`) VALUES
('Access Bank', '044'),
('Citibank', '023'),
('Ecobank', '050'),
('Fidelity Bank', '070'),
('First Bank', '011'),
('FCMB', '214'),
('Globus Bank', '103'),
('GTBank', '058'),
('Heritage Bank', '030'),
('Jaiz Bank', '301'),
('Keystone Bank', '082'),
('Kuda Bank', '50211'),
('Opay', '999991'),
('Palmpay', '999992'),
('Parallex Bank', '526'),
('Polaris Bank', '076'),
('Providus Bank', '101'),
('Stanbic IBTC Bank', '221'),
('Standard Chartered Bank', '068'),
('Sterling Bank', '232'),
('Suntrust Bank', '100'),
('Taj Bank', '302'),
('Titan Trust Bank', '102'),
('Union Bank', '032'),
('UBA', '033'),
('Unity Bank', '215'),
('Wema Bank', '035'),
('Zenith Bank', '057');
