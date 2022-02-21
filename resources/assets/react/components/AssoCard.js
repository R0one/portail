/**
 * Generate as association card.
 *
 * @author Matt Glorion <matt@glorion.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 * */

import React from 'react';
import mdeImage from '../../images/mde.svg';

const AssoCard = ({ image, login, name, shortname, additionalInfo, inCemetary }) => {
	const style = {
		backgroundImage: `url('${!image ? mdeImage : image}')`,
		backgroundSize: 'contain',
		backgroundRepeat: 'no-repeat',
	};

	return (
		<div className="asso-card">
			<div className="thumbnail" style={style}>
				<div className={`overlay ${login}`}>
					<div>{name}</div>
				</div>
			</div>
			<div className="name-container">
				<div className="asso-shortname">
					<div style={inCemetary ? { textDecorationLine: 'line-through' } : {}}>{shortname}</div>
				</div>
				{additionalInfo && <p className="w-100 text-center">{additionalInfo}</p>}
			</div>

			<div className={`card-line bg-${login}`} />
		</div>
	);
};

export default AssoCard;
