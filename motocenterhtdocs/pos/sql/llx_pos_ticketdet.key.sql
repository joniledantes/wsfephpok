-- ===================================================================
-- Copyright (C) 2011 Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_pos_ticketdet.key.sql,v 1.1 2011-08-04 16:33:26 jmenent Exp $
-- ===================================================================

ALTER TABLE llx_pos_ticketdet ADD INDEX idx_ticketdet_fk_ticket (fk_ticket);
ALTER TABLE llx_pos_ticketdet ADD INDEX idx_ticketdet_fk_product (fk_product);
ALTER TABLE llx_pos_ticketdet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_ticket);
ALTER TABLE llx_pos_ticketdet ADD CONSTRAINT fk_ticketdet_fk_ticket FOREIGN KEY (fk_ticket) REFERENCES llx_pos_ticket (rowid);
