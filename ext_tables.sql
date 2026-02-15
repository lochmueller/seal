#
# Table structure for table 'tx_seal_domain_model_index_default'
#
CREATE TABLE tx_seal_domain_model_index_default (
    location_latitude double DEFAULT 0.0 NOT NULL,
    location_longitude double DEFAULT 0.0 NOT NULL,
    FULLTEXT INDEX title (title),
    FULLTEXT INDEX titlecontent (title,content),
);