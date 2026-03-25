import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import './editor.scss';

const MODE_OPTIONS = [
	{ label: 'Mature', value: 'mature' },
	{ label: 'NSFW', value: 'nsfw' },
	{ label: 'Spoiler', value: 'spoiler' },
	{ label: 'Trigger Warning', value: 'trigger' },
];

const DEFAULT_LABELS = {
	nsfw: 'NSFW Content — Tap to reveal',
	mature: "Mature Content — Click to confirm you're 18+",
	spoiler: 'Spoiler — Click to reveal',
	trigger: 'Trigger Warning — Click to continue',
};

const REVEAL_LABELS = {
	nsfw: 'Reveal',
	mature: 'I confirm I am 18+',
	spoiler: 'Show Spoiler',
	trigger: 'Continue',
};

const SVG_ICONS = {
	mature: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
	nsfw: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
	spoiler:
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
	trigger:
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
};

const MODE_COLORS = {
	nsfw: 'rgba(180, 0, 0, 0.75)',
	mature: 'rgba(20, 20, 20, 0.82)',
	spoiler: 'rgba(88, 101, 242, 0.75)',
	trigger: 'rgba(240, 240, 240, 0.82)',
};

const MODE_TEXT_COLORS = {
	nsfw: '#fff',
	mature: '#fff',
	spoiler: '#fff',
	trigger: '#333',
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		mode,
		warningLabel,
		allowRehide,
		blurIntensity,
		rememberReveal,
		showIcon,
		buttonLabel,
		subLabel,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `matture-gate matture-gate--${ mode }`,
	} );

	const label = warningLabel || DEFAULT_LABELS[ mode ];
	const showBlur = mode === 'nsfw' || mode === 'spoiler';
	const modeOption = MODE_OPTIONS.find( ( o ) => o.value === mode );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Gate Settings', 'matture' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Mode', 'matture' ) }
						value={ mode }
						options={ MODE_OPTIONS }
						onChange={ ( val ) => setAttributes( { mode: val } ) }
					/>
					<TextControl
						label={ __( 'Warning Label', 'matture' ) }
						value={ warningLabel }
						placeholder={ DEFAULT_LABELS[ mode ] }
						onChange={ ( val ) =>
							setAttributes( { warningLabel: val } )
						}
						help={ __(
							'Leave blank to use the default label for the selected mode.',
							'matture'
						) }
					/>
					<TextControl
						label={ __( 'Reveal Button Label', 'matture' ) }
						value={ buttonLabel }
						placeholder={ REVEAL_LABELS[ mode ] }
						onChange={ ( val ) =>
							setAttributes( { buttonLabel: val } )
						}
						help={ __(
							'Leave blank to use the default button text for this mode.',
							'matture'
						) }
					/>
					<TextControl
						label={ __( 'Sub-label', 'matture' ) }
						value={ subLabel }
						placeholder={ __(
							'Optional secondary text below the warning',
							'matture'
						) }
						onChange={ ( val ) =>
							setAttributes( { subLabel: val } )
						}
						help={ __(
							'Optional secondary text displayed below the main warning label.',
							'matture'
						) }
					/>
					{ showBlur && (
						<RangeControl
							label={ __( 'Blur Intensity', 'matture' ) }
							value={ blurIntensity }
							onChange={ ( val ) =>
								setAttributes( { blurIntensity: val } )
							}
							min={ 0 }
							max={ 40 }
							step={ 1 }
						/>
					) }
					<ToggleControl
						label={ __( 'Allow Re-hide', 'matture' ) }
						checked={ allowRehide }
						onChange={ ( val ) =>
							setAttributes( { allowRehide: val } )
						}
						help={ __(
							'Show a button to re-hide content after it has been revealed.',
							'matture'
						) }
					/>
					<ToggleControl
						label={ __( 'Remember Reveal', 'matture' ) }
						checked={ rememberReveal }
						onChange={ ( val ) =>
							setAttributes( { rememberReveal: val } )
						}
						help={ __(
							'Use localStorage to remember that the user has revealed this content.',
							'matture'
						) }
					/>
					<ToggleControl
						label={ __( 'Show Mode Icon', 'matture' ) }
						checked={ showIcon }
						onChange={ ( val ) =>
							setAttributes( { showIcon: val } )
						}
						help={ __(
							'Display an icon alongside the warning label.',
							'matture'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ /* Editor overlay preview — semi-transparent so content is still editable. */ }
				<div
					className="matture-gate__overlay matture-gate__overlay--editor"
					style={ {
						background: MODE_COLORS[ mode ],
						color: MODE_TEXT_COLORS[ mode ],
					} }
				>
					{ /* Mode badge — clearly identifies which mode is active. */ }
					<span className="matture-gate__mode-badge">
						{ modeOption?.label || mode }
					</span>
					<div className="matture-gate__warning">
						{ showIcon && SVG_ICONS[ mode ] && (
							<span
								className="matture-gate__icon"
								dangerouslySetInnerHTML={ {
									__html: SVG_ICONS[ mode ],
								} }
							/>
						) }
						<span className="matture-gate__label">{ label }</span>
						{ subLabel && (
							<span className="matture-gate__sublabel">
								{ subLabel }
							</span>
						) }
						<span className="matture-gate__reveal-btn matture-gate__reveal-btn--preview">
							{ buttonLabel ||
								REVEAL_LABELS[ mode ] ||
								__( 'Reveal', 'matture' ) }
						</span>
					</div>
				</div>
				<div className="matture-gate__content matture-gate__content--editor">
					<InnerBlocks />
				</div>
			</div>
		</>
	);
}
