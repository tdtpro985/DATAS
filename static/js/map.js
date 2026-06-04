// ---------- Philippines Map (Region-based Choropleth using Province GeoJSON) ----------
const MAP_DEBUG = false; // Set to true locally to enable map diagnostic logging

let map;
let geojsonLayer;
let regionDataMap = {}; // Key: Region Name -> Data
let provinceToRegionMap = {}; // Key: Province Name -> Region Name

// M2: in-place refresh state
let mapInitialized = false;
const regionLabelRefs = {}; // { regionName: { infoEl, valueEl } }
const regionCentroids = {}; // { shortName: L.latLng } — populated during GeoJSON processing

// Fixed two-column layout for label boxes and connector lines
// Keyed by SHORT region name (values from regionShortNames)
const REGION_LAYOUT = {
    // LEFT COLUMN — spread out more vertically
    'Region 1': { side: 'left', yPct: 0.10 },
    'Region 3': { side: 'left', yPct: 0.30 },
    'Region 4-A': { side: 'left', yPct: 0.50 },
    'Region 6': { side: 'left', yPct: 0.70 },
    'Region 11': { side: 'left', yPct: 0.90 },
    // RIGHT COLUMN — spread out more vertically
    'Region 2': { side: 'right', yPct: 0.15 },
    'NCR': { side: 'right', yPct: 0.38 },
    'Region 5': { side: 'right', yPct: 0.61 },
    'Region 7': { side: 'right', yPct: 0.84 },
};
const LABEL_W = 120;  // px — matched to CSS width
const LABEL_H = 52;   // px — matched to CSS height
const COL_MARGIN = 12;  // px from container edge

async function initMap() {
    if (map) {
        // Force disable interactions on every init attempt to be sure
        map.dragging.disable();
        map.touchZoom.disable();
        map.doubleClickZoom.disable();
        map.scrollWheelZoom.disable();
        map.boxZoom.disable();
        map.keyboard.disable();
        return;
    }

    // Initialize standard Leaflet map (v27: clean slate)
    map = L.map('ph-map', {
        center: [12.0, 122.5],
        zoom: 5,
        zoomControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        touchZoom: false,
        boxZoom: false,
        keyboard: false,
        attributionControl: false,
        minZoom: 4
    });

    // Expose for external control (e.g. app.js tab switching)
    window.phMap = map;
    window.renderMapLabelsOverlay = renderMapLabelsOverlay;

    // Redraw connector lines on every zoom/pan so lines stay accurate
    map.on('zoomend moveend', () => {
        console.log('[MAP] zoomend/moveend: redrawing connector lines.');
        drawConnectorLines();
    });

    // Simple resize handler — just invalidate, do NOT re-fit (fitBounds is done once after data loads)
    const resizeObserver = new ResizeObserver(() => {
        if (map) {
            map.invalidateSize();
            // Re-render labels because their pixel positions depend on map container size
            renderMapLabelsOverlay();
        }
    });
    const mapDiv = document.getElementById('ph-map');
    if (mapDiv) resizeObserver.observe(mapDiv);

    // Global resize event often triggered by app.js or window resize
    window.addEventListener('resize', () => {
        if (map) {
            map.invalidateSize();
            setTimeout(renderMapLabelsOverlay, 150);
        }
    });

    // Initial trigger
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
            renderMapLabelsOverlay();
        }
    }, 300);

    try {
        // Fetch Local GeoJSON (Provinces)
        // Highcharts GeoJSON property for name is 'name'
        const response = await fetch((typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/static/js/ph_regions_wgs84.json');
        if (!response.ok) throw new Error("Failed to load map data");
        const geoData = await response.json();

        window.phGeoJson = geoData;
        if (MAP_DEBUG) console.log('[MAP] GeoJSON loaded', geoData.features.length, 'features');

        updateMap();
    } catch (error) {
        console.error("Error loading GeoJSON:", error);
        const mapDiv = document.getElementById('ph-map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div class="loading-text" style="padding:1rem; text-align:center;">Map unavailable</div>';
        }
    }
}

// Color Scale for Project COunt
// Categorical Palette for Regions (Distinct Colors)
// Fixed Color Mapping for Philippine Regions
const regionColors = {
    "Ilocos Region (Region I)": "#3B82F6",         // Blue
    "Cagayan Valley (Region II)": "#F97316",       // Orange
    "Central Luzon (Region III)": "#10B981",       // Emerald
    "CALABARZON (Region IV-A)": "#EAB308",         // Yellow
    "MIMAROPA (Region IV-B)": "#6366F1",           // Indigo
    "Bicol Region (Region V)": "#EC4899",          // Pink
    "Western Visayas (Region VI)": "#8B5CF6",      // Violet
    "Central Visayas (Region VII)": "#14B8A6",     // Teal
    "Eastern Visayas (Region VIII)": "#F43F5E",    // Rose
    "Zamboanga Peninsula (Region IX)": "#22C55E",  // Green
    "Northern Mindanao (Region X)": "#06B6D4",     // Cyan
    "Davao Region (Region XI)": "#A855F7",         // Purple
    "SOCCSKSARGEN (Region XII)": "#EF4444",        // Red
    "Caraga (Region XIII)": "#F59E0B",             // Amber
    "Autonomous Region of Muslim Mindanao (ARMM)": "#64748B", // Slate
    "Cordillera Administrative Region (CAR)": "#0EA5E9",      // Sky
    "Metropolitan Manila": "#D946EF"               // Fuchsia
};

function getRegionColor(name) {
    if (!name) return '#444444';
    return regionColors[name] || '#888888'; // Fallback grey
}

// Helper to get data for a feature
// Short Names for Legend (Reference Style)
const regionShortNames = {
    "Ilocos Region (Region I)": "Region 1",
    "Cagayan Valley (Region II)": "Region 2",
    "Central Luzon (Region III)": "Region 3",
    "CALABARZON (Region IV-A)": "Region 4-A",
    "MIMAROPA (Region IV-B)": "Region 4-B",
    "Bicol Region (Region V)": "Region 5",
    "Western Visayas (Region VI)": "Region 6",
    "Central Visayas (Region VII)": "Region 7",
    "Eastern Visayas (Region VIII)": "Region 8",
    "Zamboanga Peninsula (Region IX)": "Region 9",
    "Northern Mindanao (Region X)": "Region 10",
    "Davao Region (Region XI)": "Region 11",
    "SOCCSKSARGEN (Region XII)": "Region 12",
    "Caraga (Region XIII)": "Region 13",
    "Autonomous Region of Muslim Mindanao (ARMM)": "ARMM",
    "Cordillera Administrative Region (CAR)": "CAR",
    "Metropolitan Manila": "NCR"
};

// Order for Legend Display (North to South / Numerical)
const legendOrder = [
    "Cordillera Administrative Region (CAR)",
    "Ilocos Region (Region I)",
    "Cagayan Valley (Region II)",
    "Central Luzon (Region III)",
    "CALABARZON (Region IV-A)",
    "MIMAROPA (Region IV-B)",
    "Metropolitan Manila",
    "Bicol Region (Region V)",
    "Western Visayas (Region VI)",
    "Central Visayas (Region VII)",
    "Eastern Visayas (Region VIII)",
    "Zamboanga Peninsula (Region IX)",
    "Northern Mindanao (Region X)",
    "Davao Region (Region XI)",
    "SOCCSKSARGEN (Region XII)",
    "Caraga (Region XIII)",
    "Autonomous Region of Muslim Mindanao (ARMM)"
];

function getRegionDataForFeature(feature) {
    // GeoJSON property key for region name
    const regionName = feature.properties.REGION;
    if (!regionName) return null;
    return regionDataMap[regionName] || null;
}

function style(feature) {
    // Use categorical color based on Region Name
    const regionName = feature.properties.REGION;

    return {
        fillColor: getRegionColor(regionName),
        weight: 1,
        opacity: 1,
        color: '#FFFFFF', // White border for contrast
        dashArray: '',
        fillOpacity: 0.8 // High opacity for vivid colors
    };
}

function highlightFeature(e) {
    const layer = e.target;
    layer.setStyle({
        weight: 2,
        color: '#FFFFFF',
        fillOpacity: 0.9
    });
    layer.bringToFront();
}

function resetHighlight(e) {
    geojsonLayer.resetStyle(e.target);
}

function onEachFeature(feature, layer) {
    const featureRegionName = feature.properties.REGION; // The canonical region name from GeoJSON
    const provName = feature.properties.name;
    const rd = getRegionDataForFeature(feature);

    let content = `
        <div class="map-tooltip">
            <strong>${featureRegionName}</strong><br>
            <small style="color:#aaa">${provName}</small>
        </div>`;

    if (rd) {
        // API returns: { name: "Region...", value: count, total_value: total_value }
        const count = rd.value;
        const totalVal = rd.total_value;
        content = `
            <div class="map-tooltip" style="text-align: left;">
                <strong>${featureRegionName}</strong><br>
                <small>Province: ${provName}</small><br>
                Projects: <strong>${count}</strong><br>
                Value: ${formatCurrency(totalVal)}
            </div>
        `;
    }

    layer.bindTooltip(content, { sticky: true, direction: 'top', className: 'custom-tooltip' });
    layer.on({ mouseover: highlightFeature, mouseout: resetHighlight });
}

// Debugging helper
function debugJoin(feature, regionDataMap) {
    const regionName = feature.properties.REGION;
    const data = regionDataMap[regionName];

    if (!window.hasLoggedMapDebug) {
        // Log sample keys from map and API to checking alignment
        console.group("Map Join Debug");
        if (MAP_DEBUG) console.log('[MAP] Sample Feature Property:', regionName);
        if (MAP_DEBUG) console.log('[MAP] Sample RegionDataMap Key:', Object.keys(regionDataMap)[0]);
        if (MAP_DEBUG) console.log('[MAP] RegionDataMap Keys:', Object.keys(regionDataMap));
        window.hasLoggedMapDebug = true;
        console.groupEnd();
    }
    return data;
}

function updateMap() {
    if (!map || !window.phGeoJson) return;

    fetch(getApiUrl('/map/regions'))
        .then(response => response.json())
        .then(data => {
            if (MAP_DEBUG) console.log('[MAP] API Map Data:', data);

            // Build regionDataMap from fresh response
            regionDataMap = {};
            if (Array.isArray(data)) {
                data.forEach(d => {
                    if (d.name) regionDataMap[d.name] = d;
                });
            } else {
                console.error('Map API returned unexpected format:', data);
                const mapDiv = document.getElementById('ph-map');
                if (mapDiv) {
                    mapDiv.innerHTML = '<div class="loading-text" style="padding:1rem; text-align:center;">Map unavailable</div>';
                }
                return;
            }

            // M2: On subsequent renders, update labels and tooltips in-place — no layer rebuild
            if (mapInitialized) {
                console.log('[MAP] Updating label values in place (no redraw).');

                // Update HTML overlay labels
                ALLOWED_REGIONS.forEach(regionName => {
                    const d = regionDataMap[regionName];
                    const ref = regionLabelRefs[regionName];
                    if (ref) {
                        const count = d ? (d.value || 0) : 0;
                        const totalVal = d ? (d.total_value || 0) : 0;
                        if (ref.infoEl) ref.infoEl.textContent = count + ' Projects';
                        if (ref.valueEl) ref.valueEl.textContent = formatCurrency(totalVal);
                    }
                });

                // Update hover tooltip content (shown on mouseover)
                if (geojsonLayer) {
                    geojsonLayer.eachLayer(layer => {
                        const regionName = layer.feature && layer.feature.properties.REGION;
                        if (!regionName) return;
                        const d = regionDataMap[regionName];
                        const tip = '<div class="map-tooltip"><strong>' + regionName + '</strong><br/>' +
                            (d ? 'Projects: <strong>' + (d.value || 0) + '</strong><br/>Value: ' + formatCurrency(d.total_value)
                                : '<span style="color:#aaa">No active projects</span>') + '</div>';
                        layer.setTooltipContent(tip);
                    });
                }
                return; // skip full redraw
            }

            // First render — build full layer and labels
            if (geojsonLayer) map.removeLayer(geojsonLayer);

            geojsonLayer = L.geoJSON(window.phGeoJson, {
                filter: feature => ALLOWED_REGIONS.includes(feature.properties.REGION),
                style: feature => ({
                    fillColor: getRegionColor(feature.properties.REGION),
                    weight: 1, opacity: 1,
                    color: 'white', dashArray: '3', fillOpacity: 0.9
                }),
                onEachFeature: function (feature, layer) {
                    const regionName = feature.properties.REGION;
                    const d = regionDataMap[regionName];
                    let tip = '<div class="map-tooltip"><strong>' + regionName + '</strong><br/>';
                    tip += d
                        ? 'Projects: <strong>' + (d.value || 0) + '</strong><br/>Value: ' + formatCurrency(d.total_value)
                        : '<span style="color:#aaa">No active projects</span>';
                    tip += '</div>';
                    layer.bindTooltip(tip, { sticky: true, className: 'custom-tooltip' });
                    layer.on({
                        mouseover: e => { e.target.setStyle({ weight: 2, color: '#fff', dashArray: '', fillOpacity: 0.95 }); e.target.bringToFront(); },
                        mouseout: e => geojsonLayer.resetStyle(e.target)
                    });
                }
            }).addTo(map);

            if (geojsonLayer.getBounds().isValid()) {
                setTimeout(() => {
                    map.fitBounds(geojsonLayer.getBounds(), { padding: [30, 30] });
                    setTimeout(() => {
                        renderMapLabelsOverlay();
                        mapInitialized = true; // M2: mark as initialized after labels are built
                    }, 350);
                    // Draw connector lines 400ms after fitBounds — guaranteed to be after map settles
                    setTimeout(() => {
                        console.log('[MAP] setTimeout trigger: calling drawConnectorLines()');
                        drawConnectorLines();
                    }, 750);
                }, 650);
            }
        })
        .catch(err => {
            console.error('Error fetching map data:', err);
            const mapDiv = document.getElementById('ph-map');
            if (mapDiv) {
                mapDiv.innerHTML = '<div class="loading-text" style="padding:1rem; text-align:center;">Map unavailable</div>';
            }
        });
}

// (v31) Pixel-Accurate HTML Overlay Labels
// Labels are placed in CSS pixel space — no Leaflet marker clipping issues.
function renderMapLabelsOverlay() {
    if (!map || !geojsonLayer) return;

    const wrapper = document.getElementById('ph-map');
    if (!wrapper) return;

    // Remove old overlay
    const oldOverlay = document.getElementById('map-label-overlay');
    if (oldOverlay) oldOverlay.remove();

    // Create overlay container (covers the map exactly)
    const overlay = document.createElement('div');
    overlay.id = 'map-label-overlay';
    overlay.style.cssText = 'position:absolute;inset:0;pointer-events:none;z-index:1000;overflow:visible;';
    wrapper.style.position = 'relative';
    wrapper.appendChild(overlay);



    // Collect all UNIQUE region centers (one label per region, not per province)
    // Use LatLngBounds to find the true geographic center of each region.
    const regionBounds = {};
    geojsonLayer.eachLayer(layer => {
        const rn = layer.feature.properties.REGION;
        const shortKey = regionShortNames[rn] || rn;
        if (!regionBounds[shortKey]) {
            regionBounds[shortKey] = L.latLngBounds(layer.getBounds());
        } else {
            regionBounds[shortKey].extend(layer.getBounds());
        }
    });

    // Populate regionCentroids from bounds
    Object.entries(regionBounds).forEach(([key, bounds]) => {
        regionCentroids[key] = bounds.getCenter();
    });

    if (MAP_DEBUG) {
        console.log('[MAP] regionCentroids keys:', Object.keys(regionCentroids));
        console.log('[MAP] REGION_LAYOUT keys:', Object.keys(REGION_LAYOUT));
    }

    // Fixed two-column layout: defined at module level as REGION_LAYOUT
    // (moved to top of file so drawConnectorLines() can also access it)

    // Both renderMapLabelsOverlay and drawConnectorLines must use the same W/H source
    // so label box positions and line-start positions are in the same coordinate space.
    const mapContainer = window.phMap.getContainer();
    const mapW = mapContainer.offsetWidth || 640;
    const mapH = mapContainer.offsetHeight || 460;

    console.log('[MAP] Using fixed layout for', Object.keys(REGION_LAYOUT).length,
        'regions. Container:', mapW, 'x', mapH);

    ALLOWED_REGIONS.forEach(regionName => {
        const shortName = regionShortNames[regionName] || regionName;
        const layout = REGION_LAYOUT[shortName];
        if (!layout) {
            if (MAP_DEBUG) console.log('[MAP] No layout entry for region:', regionName, '(short:', shortName + ')');
            return; // skip gracefully — not in our 9-region display set
        }

        const d = regionDataMap[regionName];
        const count = d ? (d.value || 0) : 0;
        const totalVal = d ? (d.total_value || 0) : 0;

        // Fixed label position from REGION_LAYOUT
        const labelX = layout.side === 'left'
            ? COL_MARGIN
            : mapW - LABEL_W - COL_MARGIN;
        const labelY = Math.round((mapH * layout.yPct) - (LABEL_H / 2));

        // Label box — NO SVG drawing here; lines drawn by drawConnectorLines()
        const label = document.createElement('div');
        label.className = 'map-permanent-label';
        label.style.cssText = `position:absolute;left:${labelX}px;top:${labelY}px;width:${LABEL_W}px;`;

        const nameEl = document.createElement('div');
        nameEl.className = 'map-label-name';
        nameEl.textContent = shortName;

        const infoEl = document.createElement('div');
        infoEl.className = 'map-label-info';
        infoEl.textContent = count + ' Projects';

        const valueEl = document.createElement('div');
        valueEl.className = 'map-label-value';
        valueEl.textContent = formatCurrency(totalVal);

        label.appendChild(nameEl);
        label.appendChild(infoEl);
        label.appendChild(valueEl);
        overlay.appendChild(label);

        // M2: Store refs to mutable elements for in-place refresh updates
        regionLabelRefs[regionName] = { infoEl, valueEl };

        if (MAP_DEBUG) console.log('[MAP] Label placed:', regionName,
            '→', layout.side, 'col at y%', layout.yPct);
    });

    if (MAP_DEBUG) console.log('[MAP] Labels positioned for',
        Object.keys(regionDataMap).length, 'regions');
    // SVG connector lines are drawn by the module-level drawConnectorLines()
    // called via setTimeout after fitBounds and on zoomend/moveend
}

// ---------- Module-level SVG Connector Lines ----------
// Must be outside renderMapLabelsOverlay so it can be called from updateMap()
// and from the permanent zoomend/moveend listener.
function drawConnectorLines() {
    console.log('[MAP] drawConnectorLines() called.');
    if (!window.phMap) { console.warn('[MAP] phMap not ready'); return; }

    // Append to Leaflet map container directly — avoids any overflow:hidden clipping
    const mapContainer = window.phMap.getContainer();

    // Remove previous connector SVG if it exists
    const existingSvg = mapContainer.querySelector('svg.connector-lines');
    if (existingSvg) {
        existingSvg.remove();
        console.log('[MAP] Removed previous connector SVG.');
    }

    // Read container size fresh each call (handles resize)
    const W = mapContainer.offsetWidth || 640;
    const H = mapContainer.offsetHeight || 460;
    console.log('[MAP] Container size:', W, 'x', H);

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.classList.add('connector-lines');
    svg.style.cssText = [
        'position:absolute',
        'top:0',
        'left:0',
        'width:' + W + 'px',
        'height:' + H + 'px',
        'pointer-events:none',
        'z-index:1000',
        'overflow:visible'
    ].join(';');

    let linesDrawn = 0;
    Object.entries(REGION_LAYOUT).forEach(([regionName, layout]) => {
        const centroidLatLng = regionCentroids[regionName];
        if (!centroidLatLng) {
            console.warn('[MAP] No centroid found for:', regionName);
            return;
        }

        // latLngToContainerPoint is accurate because map has settled before this call
        const pt = window.phMap.latLngToContainerPoint(centroidLatLng);
        console.log('[MAP] Centroid px for', regionName, '→', pt.x.toFixed(1), pt.y.toFixed(1));

        const labelY = (H * layout.yPct) - (LABEL_H / 2);
        const labelX = layout.side === 'left'
            ? COL_MARGIN
            : W - LABEL_W - COL_MARGIN;
        const lineStartX = layout.side === 'left'
            ? labelX + LABEL_W  // right edge of left-column label
            : labelX;           // left edge of right-column label
        const lineStartY = labelY + (LABEL_H / 2);

        // Connector line
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', String(lineStartX));
        line.setAttribute('y1', String(lineStartY));
        line.setAttribute('x2', String(pt.x));
        line.setAttribute('y2', String(pt.y));
        line.setAttribute('stroke', '#f59e0b');
        line.setAttribute('stroke-width', '1.5');
        line.setAttribute('opacity', '0.8');
        svg.appendChild(line);

        // Centroid dot
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', String(pt.x));
        dot.setAttribute('cy', String(pt.y));
        dot.setAttribute('r', '4');
        dot.setAttribute('fill', '#f59e0b');
        svg.appendChild(dot);

        linesDrawn++;
        if (MAP_DEBUG) console.log('[MAP] lineStart:', regionName,
            'labelX:', labelX.toFixed(0), 'lineStartX:', lineStartX.toFixed(0),
            'lineStartY:', lineStartY.toFixed(0),
            '→ centroid:', pt.x.toFixed(1), pt.y.toFixed(1),
            'W:', W, 'H:', H);
    });

    // Append LAST so all elements are ready before paint
    mapContainer.appendChild(svg);
    console.log('[MAP] SVG appended to map container.', linesDrawn, 'lines drawn.');
}


// ---------- Map Auto-Pan Logic ----------
function startMapRotation() {
    if (mapRotationTimer) clearInterval(mapRotationTimer);
    mapRotationTimer = setInterval(rotateMap, MAP_ROTATION_INTERVAL);
}

function rotateMap() {
    if (isMapHovered || !geojsonLayer || !map) return;

    // Cycle through -1 (Full View/Overview) to (ALLOWED_REGIONS.length - 1)
    mapRotationIndex++;
    if (mapRotationIndex >= ALLOWED_REGIONS.length) {
        mapRotationIndex = -1; // Reset to Full View
    }

    const showcasePanel = document.getElementById('map-showcase-panel');

    if (mapRotationIndex === -1) {
        // --- State: Full View (Restore All) ---
        if (showcasePanel) showcasePanel.classList.remove('active');

        geojsonLayer.eachLayer(layer => {
            const el = layer.getElement();
            if (el) el.style.opacity = '1';
            geojsonLayer.resetStyle(layer);
        });

        if (window.regionLabelMarkers) {
            Object.values(window.regionLabelMarkers).forEach(marker => {
                const el = marker.getElement();
                if (el) {
                    el.style.opacity = '1';
                    el.style.display = 'flex';
                }
            });
        }

        // Restore leader lines and dots
        if (window.regionLeaderLines) {
            Object.values(window.regionLeaderLines).forEach(line => {
                const el = line.getElement();
                if (el) el.style.opacity = '0.4';
            });
        }
        if (window.regionCenterDots) {
            Object.values(window.regionCenterDots).forEach(dot => {
                const el = dot.getElement();
                if (el) el.style.opacity = '0.8';
            });
        }

        if (geojsonLayer.getBounds().isValid()) {
            map.flyToBounds(geojsonLayer.getBounds(), {
                padding: [25, 25],
                duration: 2.0
            });
        }
    } else {
        // --- State: regional Showcase (Isolate & Zoom) ---
        const targetRegion = ALLOWED_REGIONS[mapRotationIndex];
        let targetLayer = null;
        let regionData = window.currentMapData ? window.currentMapData.find(d => d.region === targetRegion) : null;

        // 1. Isolate Region & Highlight with robust matching
        const normalizedTarget = targetRegion.trim().toLowerCase();

        geojsonLayer.eachLayer(layer => {
            const featureRegion = (layer.feature?.properties?.REGION || "").trim().toLowerCase();
            const el = layer.getElement();

            if (featureRegion === normalizedTarget) {
                targetLayer = layer;
                if (el) el.style.opacity = '1';
                layer.setStyle({
                    weight: 3,
                    color: '#FF9800',
                    fillColor: '#FF9800',
                    fillOpacity: 0.25,
                    opacity: 1
                });
                layer.bringToFront();
            } else {
                if (el) el.style.opacity = '0';
                layer.setStyle({ opacity: 0, fillOpacity: 0 });
            }
        });

        // 2. Populate Premium Info Panel
        if (showcasePanel && targetLayer) {
            const shortName = regionShortNames[targetRegion] || targetRegion;
            const count = regionData ? (regionData.count || 0) : 0;
            const totalVal = regionData ? (regionData.total_value || 0) : 0;
            const formattedVal = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 0
            }).format(totalVal);

            showcasePanel.innerHTML = `
                <div class="region-title">${shortName}</div>
                <div class="data-row">
                    <span class="data-label">Projects</span>
                    <span class="data-value">${count}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Pipeline Value</span>
                </div>
                <div class="data-row">
                    <span class="value-accent">${formattedVal}</span>
                </div>
            `;
            showcasePanel.classList.add('active');
        }

        // 3. Hide all floating labels and lines during zoom for cleanliness
        if (window.regionLabelMarkers) {
            Object.values(window.regionLabelMarkers).forEach(m => {
                const el = m.getElement();
                if (el) el.style.opacity = '0';
            });
        }
        if (window.regionLeaderLines) {
            Object.values(window.regionLeaderLines).forEach(l => {
                const el = l.getElement();
                if (el) el.style.opacity = '0';
            });
        }
        if (window.regionCenterDots) {
            Object.values(window.regionCenterDots).forEach(d => {
                const el = d.getElement();
                if (el) el.style.opacity = '0';
            });
        }

        // 4. Side-by-Side Presentation Zoom (Swapped: Region on Right)
        if (targetLayer) {
            // We use massive LEFT padding to push the region to the RIGHT
            // allowing the Label HUD to sit on the LEFT side specifically.
            const mapWidth = map.getSize().x;
            const leftOffset = Math.min(380, Math.floor(mapWidth * 0.42)); // Reserve space on the left

            map.flyToBounds(targetLayer.getBounds(), {
                paddingTopLeft: [leftOffset, 40],
                paddingBottomRight: [40, 40],
                duration: 2.5,
                easeLinearity: 0.25
            });
        }
    }
}

