/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export class Color {

  /**
   * @param r The red value
   * @param g The green value
   * @param b The blue value
   * @param [name] The name of the color
   */
  constructor(r: number, g: number, b: number, name?: string) {
    this.r = r
    this.g = g
		this.b = b
    if (name) {
      this.name = name
    }
  }

  r: number;
  g: number;
  b: number;
  name?: string;

  get color() {
    const toHex = (num: number) => `00${num.toString(16)}`.slice(-2)
    return `#${toHex(this.r)}${toHex(this.g)}${toHex(this.b)}`
  }

  toString() {
    return this.color + (this.name ? ' (' + this.name + ')' : '')
  }

  toJSON() {
    return { r: this.r, g: this.g, b: this.b, name: this.name, color: this.color };
  }
}
