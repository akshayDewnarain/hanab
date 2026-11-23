import { layoutOrganogram, initialCollapsed } from '../src/composables/useOrganogramLayout.ts';
import type { OrganogramEdge, OrganogramNode } from '../src/modules/support/organogram/types.ts';

function employee(id: number, locationId: number | null, location: string | null, extra: Partial<OrganogramNode> = {}): OrganogramNode {
    return {
        id: `employee-${id}`,
        type: 'employee',
        name: `Person ${id}`,
        employeeId: id,
        locationId,
        location,
        ...extra,
    };
}

function edge(source: number, target: number): OrganogramEdge {
    return {
        id: `report-${source}-${target}`,
        source: `employee-${source}`,
        target: `employee-${target}`,
        type: 'reports_to',
    };
}

function overlaps(left: { x: number; y: number; width: number; height: number }, right: { x: number; y: number; width: number; height: number }): boolean {
    return left.x < right.x + right.width - 1 && left.x + left.width > right.x + 1 && left.y < right.y + right.height - 1 && left.y + left.height > right.y + 1;
}

function assert(condition: boolean, message: string): void {
    if (!condition) {
        throw new Error(message);
    }
}

const nodes = [
    employee(1, 1, 'Amsterdam'),
    employee(2, 1, 'Amsterdam'),
    employee(7, 1, 'Amsterdam'),
    employee(3, 1, 'Amsterdam'),
    employee(4, 2, 'Utrecht', { reportsElsewhere: true }),
    employee(5, 2, 'Utrecht'),
    employee(6, 3, 'Groningen', { managerNote: 'inactive', managerName: 'Inactive lead' }),
];
const edges = [edge(1, 2), edge(1, 4), edge(2, 7)];

const grouped = await layoutOrganogram(nodes, edges, {
    groupByLocation: true,
    locationOrder: [3, 1, 2],
    unassignedLabel: 'Employees without an assigned manager',
    noLocationLabel: 'No location',
});

const people = grouped.filter((node) => node.type === 'employee');
const locations = grouped.filter((node) => node.groupKind === 'location');
assert(people.length === nodes.length, 'location layout kept every employee');
assert(new Set(people.map((node) => node.id)).size === people.length, 'each employee appears once');
assert(locations.length === 3, 'one container per location');
assert(locations.map((node) => node.id).join(',') === 'location-3,location-1,location-2', 'containers follow the location list order');
assert(locations.every((location) => location.y === locations[0]!.y), 'location containers share one top edge');
for (let index = 1; index < locations.length; index += 1) {
    const previous = locations[index - 1]!;
    const current = locations[index]!;
    assert(current.x > previous.x, 'locations proceed from left to right');
    assert(Math.abs(current.x - (previous.x + previous.width) - 56) < 1, 'location spacing stays consistent');
}

function contains(container: (typeof locations)[number], node: (typeof people)[number]): boolean {
    return node.x >= container.x && node.y >= container.y && node.x + node.width <= container.x + container.width + 1 && node.y + node.height <= container.y + container.height + 1;
}

for (const location of locations) {
    const members = people.filter((node) => contains(location, node));
    assert(location.memberCount === members.length, `${location.name} count matches the employees drawn inside it`);
}

for (const person of people) {
    const home = locations.find((location) => location.id === (person.locationId == null ? 'location-none' : `location-${person.locationId}`));
    const above = person.y + person.height <= locations[0]!.y + 1;
    assert((home !== undefined && contains(home, person)) || above, `${person.name} stays in their location or in the shared band`);
}

for (let index = 0; index < locations.length; index += 1) {
    for (const other of locations.slice(index + 1)) {
        assert(!overlaps(locations[index]!, other), 'location containers do not overlap');
    }
}

for (let index = 0; index < people.length; index += 1) {
    for (const other of people.slice(index + 1)) {
        assert(!overlaps(people[index]!, other), 'employee cards do not overlap');
    }
}

const manager = people.find((node) => node.id === 'employee-1');
const localManager = people.find((node) => node.id === 'employee-2');
const localReport = people.find((node) => node.id === 'employee-7');
const loose = people.find((node) => node.id === 'employee-3');
const cross = people.find((node) => node.id === 'employee-4');
const banner = grouped.find((node) => node.id === 'unassigned-1');
assert(
    manager !== undefined && localManager !== undefined && localReport !== undefined && loose !== undefined && cross !== undefined && banner !== undefined,
    'expected nodes are present',
);
assert(manager!.y + manager!.height <= locations[0]!.y, 'a manager of several locations sits above the location row');
assert(localReport!.y >= localManager!.y + localManager!.height - 1, 'a direct report sits below their manager');
assert(
    Math.abs(localReport!.x + localReport!.width / 2 - (localManager!.x + localManager!.width / 2)) < 24,
    'a single report is centered under the manager',
);
assert(loose!.y >= banner!.y + banner!.height, 'an employee with no manager sits in the unassigned section');
const amsterdam = locations.find((location) => location.id === 'location-1');
assert(amsterdam !== undefined, 'Amsterdam is present');
assert(
    Math.abs(localManager!.x + localManager!.width / 2 - (amsterdam!.x + amsterdam!.width / 2)) < 16,
    'a location hierarchy is centered in its container',
);
assert(cross!.y < (grouped.find((node) => node.id === 'unassigned-2')?.y ?? Number.POSITIVE_INFINITY), 'a cross-location report is not placed with unassigned people');
assert(!grouped.some((node) => node.type === 'employee' && !nodes.some((source) => source.id === node.id)), 'layout does not invent employees');

const plain = await layoutOrganogram(nodes, edges, { unassignedLabel: 'Employees without an assigned manager' });
const plainPeople = plain.filter((node) => node.type === 'employee');
const connectedBottom = Math.max(
    ...plainPeople.filter((node) => node.id === 'employee-1' || node.id === 'employee-2' || node.id === 'employee-4').map((node) => node.y),
);
const looseTop = Math.min(...plainPeople.filter((node) => node.id === 'employee-3' || node.id === 'employee-5').map((node) => node.y));
assert(looseTop > connectedBottom, 'unassigned employees sit below the reporting trees');
assert(!plain.some((node) => node.groupKind === 'location'), 'management view does not draw location containers');

const wide = [
    employee(10, null, null),
    ...Array.from({ length: 8 }, (_, index) => employee(11 + index, null, null)),
    employee(30, null, null),
];
const wideEdges = wide.slice(1, 9).map((node) => edge(10, node.employeeId!));
const wideLaid = await layoutOrganogram(wide, wideEdges, { unassignedLabel: 'Employees without an assigned manager' });
const wideRoot = wideLaid.find((node) => node.id === 'employee-10');
const wideLoose = wideLaid.find((node) => node.id === 'employee-30');
const wideChild = wideLaid.find((node) => node.id === 'employee-11');
assert(wideRoot !== undefined && wideLoose !== undefined && wideChild !== undefined, 'wide chart nodes exist');
assert(wideLoose!.y > wideRoot!.y + wideRoot!.height, 'a disconnected employee is not parked beside a wide team');
const wideChildren = wideLaid.filter((node) => node.id.startsWith('employee-1') && node.id !== 'employee-10');
const childLeft = Math.min(...wideChildren.map((node) => node.x));
const childRight = Math.max(...wideChildren.map((node) => node.x + node.width));
assert(Math.abs(wideRoot!.x + wideRoot!.width / 2 - (childLeft + childRight) / 2) < 20, 'a manager is centered over a wide team');

const deep: OrganogramNode[] = [];
const deepEdges: OrganogramEdge[] = [];
for (let id = 1; id <= 45; id += 1) {
    deep.push(employee(id, 1, 'Amsterdam'));
    if (id > 1) {
        deepEdges.push(edge(id - 1, id));
    }
}
const collapsed = initialCollapsed(deep, deepEdges);
assert(collapsed.size > 0, 'a long chart collapses deeper branches');
assert(!collapsed.has('employee-1') && !collapsed.has('employee-2'), 'the top of the chart stays open');

async function time(count: number): Promise<number> {
    const crowd: OrganogramNode[] = [];
    const links: OrganogramEdge[] = [];
    for (let id = 1; id <= count; id += 1) {
        const locationId = (id % 5) + 1;
        crowd.push(employee(id, locationId, `Location ${locationId}`));
        if (id > 5) {
            links.push(edge(((id - 1) % 40) + 1, id));
        }
    }
    const started = performance.now();
    const laid = await layoutOrganogram(crowd, links, {
        groupByLocation: true,
        locationOrder: [1, 2, 3, 4, 5],
        unassignedLabel: 'Employees without an assigned manager',
    });
    const locationBoxes = laid.filter((node) => node.groupKind === 'location');
    assert(locationBoxes.length === 5, `five locations at ${count} employees`);
    assert(
        locationBoxes.every((box) => box.y === locationBoxes[0]!.y),
        `locations stay on one horizontal line at ${count} employees`,
    );
    for (let index = 0; index < locationBoxes.length; index += 1) {
        for (const other of locationBoxes.slice(index + 1)) {
            assert(!overlaps(locationBoxes[index]!, other), `location containers overlap at ${count} employees`);
        }
    }
    return performance.now() - started;
}

const fifty = await time(50);
const twoFifty = await time(250);
const thousand = await time(1000);
console.log(`layout ms: 50=${fifty.toFixed(0)} 250=${twoFifty.toFixed(0)} 1000=${thousand.toFixed(0)}`);
console.log('organogram layout checks passed');
