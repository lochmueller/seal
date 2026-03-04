#
# Table structure for table 'tx_seal_domain_model_index_default'
#
CREATE TABLE tx_seal_domain_model_index_default (
    location_latitude double DEFAULT 0.0 NOT NULL,
    location_longitude double DEFAULT 0.0 NOT NULL,
    FULLTEXT INDEX title (title),
    FULLTEXT INDEX titlecontent (title,content),
);

#
# Table structure for table 'tx_seal_domain_model_stat'
#
CREATE TABLE tx_seal_domain_model_stat (
    site varchar(255) DEFAULT '' NOT NULL,
    language varchar(50) DEFAULT '' NOT NULL,
    search_term varchar(255) DEFAULT '' NOT NULL,
);
