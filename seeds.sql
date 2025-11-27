INSERT INTO `data_plans` (`network`, `type`, `quantity`, `price`) VALUES
('mtn', 'sme-data', '1gb', 590.00),
('mtn', 'sme-data', '2gb', 1180.00),
('mtn', 'sme-data', '3gb', 1770.00),
('mtn', 'sme-data', '5gb', 2950.00),
('mtn', 'cg-data', '500mb', 345.00),
('mtn', 'cg-data', '1gb', 485.00);

INSERT INTO `services` (`name`, `description`, `is_available`) VALUES
('Buy Airtime', 'Purchase airtime for any network.', 1),
('Buy Data', 'Purchase data bundles for any network.', 1),
('Pay Cable TV', 'Pay your cable TV subscriptions.', 1),
('Pay Electricity', 'Pay your electricity bills.', 1),
('Exam Pins', 'Purchase exam pins.', 1);

INSERT INTO `networks` (`name`, `code`) VALUES
('MTN', 'mtn'),
('Glo', 'glo'),
('Airtel', 'airtel'),
('9mobile', '9mobile');
