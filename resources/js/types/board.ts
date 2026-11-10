export type ColumnKind = 'list' | 'day' | 'done';

export type SubTask = {
    id: number;
    name: string;
    finished: boolean;
};

export type Task = {
    /** Negative while an optimistic create or copy is waiting for the server. */
    id: number;
    name: string;
    color: string;
    /** ISO 8601 in UTC, or null when the card is not done. */
    completedAt: string | null;
    subTasks: SubTask[];
};

export type Column = {
    id: number;
    name: string;
    kind: ColumnKind;
    wipLimit: number | null;
    tasks: Task[];
};

export type Tint = {
    bg: string;
    dot: string;
    text: string;
    /** The user's project name for this colour, if any. */
    project: string | null;
};

export type Palette = Record<string, Tint>;

export type BoardPageProps = {
    board: Column[];
    palette: Palette;
    defaultColor: string;
    timezone: string;
};

export type DoneGroup = {
    /** YYYY-MM-DD in the user's timezone, or null for cards with no date. */
    key: string | null;
    label: string;
    tasks: Task[];
};
