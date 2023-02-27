-- Description:
-- >>> Up >>>
CREATE TABLE IF NOT EXISTS `{!!prefix!!}tr_blocks` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int DEFAULT NULL,
    `last_user_id` int DEFAULT NULL,
    `title` varchar(255) CHARACTER SET {!!charset!!} COLLATE {!!collate!!} NOT NULL,
    `blocks` longtext CHARACTER SET {!!charset!!} COLLATE {!!collate!!},
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET={!!charset!!} COLLATE={!!collate!!};

CREATE TABLE IF NOT EXISTS `{!!prefix!!}tr_builder_revisions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `post_id` int DEFAULT NULL,
    `user_id` int DEFAULT NULL,
    `model` varchar(255) CHARACTER SET {!!charset!!} COLLATE {!!collate!!} NOT NULL,
    `field_name` varchar(255) CHARACTER SET {!!charset!!} COLLATE {!!collate!!} NOT NULL,
    `components` longtext CHARACTER SET {!!charset!!} COLLATE {!!collate!!},
    `created_at` datetime DEFAULT NULL,
     PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET={!!charset!!} COLLATE={!!collate!!};

-- >>> Down >>>
DROP TABLE IF EXISTS `{!!prefix!!}tr_builder_revisions`;
DROP TABLE IF EXISTS `{!!prefix!!}tr_blocks`;